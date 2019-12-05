<?php

namespace Anboo\ApiBundle\Consumer;

use Doctrine\Common\Annotations\Reader;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Anboo\ApiBundle\AMQP\AMQPConnection;
use Anboo\ApiBundle\AMQP\Exception\AmqpFatalException;
use Anboo\ApiBundle\AMQP\Packet;
use Anboo\ApiBundle\AMQP\Producer;
use Anboo\ApiBundle\Annotation\Enqueue\Consume;
use Anboo\ApiBundle\Annotation\Enqueue\CrudConsume;
use Anboo\ApiBundle\Annotation\Enqueue\CrudProduce;
use Anboo\ApiBundle\Annotation\Enqueue\Produce;
use Anboo\ApiBundle\Exception\ConnectionTimeoutException;
use Anboo\ApiBundle\Monolog\RabbitMqContext;
use Webslon\Library\Api\Exception\ApiException;
use Webslon\Library\Api\Service\HandlerException\Validation\ValidationException;
use Webslon\Library\Api\Service\HandlerException\ValidationAndNormalizationException;
use Webslon\Library\Serializer\Exception\DenormalizeException;

abstract class AbstractCrudConsumer extends AbstractConsumer
{
    /**
     * @var string
     */
    protected $topicName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Producer
     */
    protected $producer;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var AMQPConnection
     */
    private $connection;

    /**
     * @required
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @required
     *
     * @param Producer $producer
     *
     * @return $this
     */
    public function setProducer(Producer $producer): self
    {
        $this->producer = $producer;

        return $this;
    }

    /**
     * @required
     *
     * @param Reader $annotationReader
     */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @required
     *
     * @param AMQPConnection $connection
     */
    public function setConnection(AMQPConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Packet $packet
     *
     * @return object|string|void
     * @throws \ReflectionException
     */
    public function process(Packet $packet)
    {
        try {
            $this->doProcess($packet);
            $this->ack();
        } catch (ConnectionTimeoutException $exception){
            $this->resolveFailureProcess($packet, $exception);
        } catch (AMQPConnectionClosedException $exception){
            $this->resolveFailureProcess($packet, $exception);
        } catch (ValidationAndNormalizationException $validationAndNormalizationException) {
            $this->resolveFailureProcess($packet, $validationAndNormalizationException);
        } catch (ValidationException $validationException) {
            $this->resolveFailureProcess($packet, $validationException);
        } catch (\Exception $exception){
            $this->resolveFailureProcess($packet, $exception);
        } catch (\Throwable $exception) {
            $this->resolveFailureProcess($packet, $exception);
        }
    }

    /**
     * @param Packet $packet
     * @param \Exception|\Error $exception
     * @return bool
     * @throws \ReflectionException
     */
    protected function resolveFailureProcess(Packet $packet,  $exception)
    {
        $this->handleException($packet, $exception);
        /** @var CrudConsume $annotation */
        $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($this->entityClass), CrudConsume::class);
        $newPacket = Packet::createFromData($packet->getData(),null, $packet->getErrors());

        $data = json_encode($newPacket);
        if (array_filter($this->getReplyData($packet))) {
            $this->reply(json_decode(json_encode(Packet::createFromData($packet->getData(),null, $packet->getErrors())), true));
        }
        if ($annotation instanceof CrudConsume) {
            $consumer = $annotation->topicsMap[$this->topicName] ?? null;
            if ($consumer instanceof Consume && $consumer->onErrors) {
                if ($consumer->onErrors->exchange && $consumer->onErrors->routingKey) {
                    list($exchangeName) = $this->producer->getPlaceholderResolver()->handlePlaceholdersParameters($consumer->onErrors->exchange);
                    $this->producer->getChannel()->exchange_declare($exchangeName, 'direct', false,true, false, '');
                    $this->producer->publishToExchange($exchangeName, $data, $consumer->onErrors->routingKey, ['routing_key' => $consumer->onErrors->routingKey]);
                    $this->reject(false);

                    return true;
                }
                if($consumer->onErrors->queue) {
                    list($queue) = $this->producer->getPlaceholderResolver()->handlePlaceholdersParameters($consumer->onErrors->queue);
                    $this->producer->publishToQueue($queue, $data);
                    $this->reject(false);

                    return true;
                }
            }
        } else {
            $this->reject();

            return true;
        }
        $this->ack();
    }

    /**
     * @param Packet     $packet
     * @param \Exception|\Throwable $ex
     *
     * @throws \ReflectionException
     */
    protected function handleException(Packet $packet, $ex)
    {
        $errors = [];
        if($ex instanceof ValidationException){
            foreach ($ex->getConstraints() ? : [] as $constraint) {
                $errorItem = sprintf('[%s] %s', $constraint->getPropertyPath(), $constraint->getMessage());
                $ex = (new ApiException($errorItem, 'Error_' . Response::HTTP_BAD_REQUEST, $constraint->getPropertyPath(),Response::HTTP_BAD_REQUEST))
                    ->setType(ValidationException::class)
                ;
                $packet->addError($ex);
                $errors[] = $errorItem;
            }
            $this->logger->error(sprintf('Validation errors: %s', implode(',', $errors)), RabbitMqContext::getLoggingContext($packet, $ex));
        } elseif ($ex instanceof ValidationAndNormalizationException){
            $validationErrors = [];
            foreach ($ex->getValidationError() ?: [] as $violation) {
                $errorItem = sprintf('[%s] %s', $violation->getPropertyPath(), $violation->getMessage());
                $packet->addError(new ApiException($errorItem, 'Error_' . Response::HTTP_BAD_REQUEST, $violation->getPropertyPath(), Response::HTTP_BAD_REQUEST));
                $validationErrors[] = $errorItem;
            }
            $normalizationErrors = array_map(function ($error) {
                if (is_array($error) && isset($error[0]) && $error[0] instanceof DenormalizeException) {
                    return $error[0]->getMessage();
                }
                if (is_scalar($error)) {
                    return $error;
                }
                return gettype($error);
            }, $ex->getDenormalizationError() ?: []);
            $ex = (new ApiException(implode(',', $normalizationErrors), 'Error_' . Response::HTTP_BAD_REQUEST, null, Response::HTTP_BAD_REQUEST))
                ->setType(ValidationAndNormalizationException::class)
            ;
            $packet->addError($ex);
            $this->logger->error(sprintf(
                'Validation errors: %s normalized errors: %s',
                implode(',', $validationErrors),
                implode(',', $normalizationErrors)
            ), RabbitMqContext::getLoggingContext($packet, $ex));
        } else {
            if (method_exists($ex, 'getStatusCode')) {
                $code = $ex->getStatusCode();
            }else{
                $code = $ex->getCode();
            }
            if (!isset(Response::$statusTexts[$code])) {
                $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            $ex = (new ApiException($ex->getMessage(), 'Error_' . $code, null, $code))
                ->setType((new \ReflectionClass($ex))->getName())
                ->setErrorTrace(getenv('APP_ENV') !== 'prod' ? $ex->getTraceAsString(): null)
            ;

            $packet->addError($ex);
            $this->logger->error(sprintf(
                'Exception: %s',
                $ex->getMessage()
            ), RabbitMqContext::getLoggingContext($packet, $ex));
        }
    }

    abstract public function doProcess(Packet $packet);
}
