<?php

namespace Anboo\RabbitmqBundle\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Anboo\RabbitmqBundle\AMQP\RPC\Exchange;
use Anboo\RabbitmqBundle\AMQP\RPC\ReplyContextTrait;
use Anboo\RabbitmqBundle\AMQP\RPC\ResponseStorageInterface;
use Anboo\RabbitmqBundle\AMQP\RPC\RpcManager;
use Anboo\RabbitmqBundle\Model\Model;
use Webslon\Bundle\AuthBundle\Model\AuthenticationInformation;
use Webslon\Bundle\AuthBundle\Model\User;
use Webslon\Bundle\AuthBundle\Security\SecurityManager;

/**
 * Class Producer
 */
class Producer
{
    use ReplyContextTrait;

    /**
     * @var AMQPConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var PlaceholderResolver
     */
    private $placeholderResolver;

    /**
     * @var string
     */
    private $responseQueueCallback;

    /**
     * @var int
     */
    private $lifetimeCallbackRpcQueue;

    /**
     * @var RpcManager
     */
    private $rpcManager;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var ResponseStorageInterface
     */
    protected $responseStorage;

    protected static $propertyDefinitions = array(
        'content_type',
        'content_encoding',
        'application_headers',
        'delivery_mode',
        'priority',
        'correlation_id',
        'reply_to',
        'expiration',
        'message_id',
        'timestamp',
        'type',
        'user_id',
        'app_id',
        'cluster_id',
        'deliveryMode'
    );

    /**
     * @return RpcManager
     */
    public function getRpcManager(): RpcManager
    {
        return $this->rpcManager;
    }

    /**
     * @param string $responseQueueCallback
     */
    public function setResponseQueueCallback(string $responseQueueCallback)
    {
        $this->responseQueueCallback = $responseQueueCallback;
    }

    /**
     * @return string
     */
    public function getResponseQueueCallback(): string
    {
        return $this->responseQueueCallback;
    }

    /**
     * @param int $lifetimeCallbackRpcQueue
     */
    public function setLifetimeCallbackRpcQueue(int $lifetimeCallbackRpcQueue)
    {
        $this->lifetimeCallbackRpcQueue = $lifetimeCallbackRpcQueue;
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
     * @required
     *
     * @param PlaceholderResolver $placeholderResolver
     */
    public function setPlaceholderResolver(PlaceholderResolver $placeholderResolver)
    {
        $this->placeholderResolver = $placeholderResolver;
    }

    /**
     * @return PlaceholderResolver
     */
    public function getPlaceholderResolver()
    {
        return $this->placeholderResolver;
    }

    /**
     * @required
     *
     * @param ResponseStorageInterface $responseStorage
     */
    public function setResponseStorage(ResponseStorageInterface $responseStorage)
    {
        $this->responseStorage = $responseStorage;
    }

    /**
     * @required
     *
     * @param RpcManager $rpcManager
     */
    public function setRpcManager(RpcManager $rpcManager)
    {
        $this->rpcManager = $rpcManager;
    }

    /**
     * @return AMQPConnection
     */
    public function getConnection():AMQPConnection
    {
        return $this->connection;
    }

    /**
     * @param AMQPChannel $channel
     *
     * @return $this
     */
    public function setChannel(AMQPChannel $channel):self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel():AMQPChannel
    {
        if (!$this->channel instanceof AMQPChannel || !$this->channel->is_open()) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    /**
     * @param string $remoteProcedure
     * @param Packet|array  $message
     * @param string $correlationId
     * @param string $replyTo
     * @param array  $options
     *
     * @return string
     */
    public function rpcCallAsync($remoteProcedure, $message, string $correlationId = null, $replyTo = null, array $options = [])
    {
        $correlationId = $correlationId ?? (string)Uuid::uuid4();

        if ($message instanceof Packet) {
            $packet = $message;
        } else {
            $packet = new Packet(
                $correlationId,
                new \DateTime(),
                $message,
                Packet::RPC_ASYNC
            );
        }

        if ($remoteProcedure instanceof Exchange) {
            $exchange = $remoteProcedure->getExchange();
            $routingKey = $remoteProcedure->getRoutingKey();
        } else {
            $exchange = '';
            $routingKey = $remoteProcedure;

            $this->getChannel()->queue_declare($remoteProcedure, false, true, false, false, false);
        }

        if ($replyTo instanceof Exchange) {
            if ($replyTo->isExchangeDestination()) {
                $options['reply_to.exchange'] = $replyTo->getExchange();
                $options['reply_to.routing_key'] = $replyTo->getRoutingKey();
            }

            if ($replyTo->isQueueDestination()) {
                $options['reply_to'] = $replyTo->getRoutingKey();
            }
        } else {
            $options['reply_to'] = $replyTo;
        }

        if (!isset($options['reply_to.exchange']) && !isset($options['reply_to'])) {
            $options['reply_to'] = $this->responseQueueCallback;
        }

        $this->rpcManager->declareRpcCallbackQueue($this->getChannel(), $correlationId);

        $options = array_merge([
            'deliveryMode' => 2,
            'correlation_id' => $correlationId,
        ], $options);

        $this->sendPacket($exchange, $packet, $routingKey, $options);

        return $correlationId;
    }

    /**
     * @param string $requestId
     */
    public function waitResponse(string $requestId)
    {
        $this->responseStorage->waitResponse($requestId);
    }

    /**
     * @param string $requestId
     *
     * @return array
     */
    public function getResponse(string $requestId)
    {
        return $this->responseStorage->getResponse($requestId);
    }

    /**
     * @param string $exchange
     * @param array  $message
     * @param string $routingKey
     * @param array  $errors
     *
     * @throws \Exception
     */
    public function sendMessage(string $exchange, array $message, string $routingKey = null, array $errors = [])
    {
        $this->sendPacket(
            $exchange,
            new Packet(null, new \DateTime(), $message, $errors),
            $routingKey
        );
    }

    /**
     * @param string             $exchange
     * @param AMQPMessage|string $message
     * @param string             $routingKey
     * @param array              $options
     */
    public function publishToExchange($exchange, $message, string $routingKey = null, $options = [])
    {
        if (!$message instanceof AMQPMessage) {
            $options = array_merge(['deliveryMode' => 2], $options);
            $message = new AMQPMessage($message, $options);

            $headers = [];
            foreach ($options as $option => $optionValue) {
                if (!in_array($option, self::$propertyDefinitions)) {
                    $headers[$option] = $optionValue;
                }
            }

            if ($headers) {
                $message->set('application_headers', new AMQPTable($headers));
            }
        }

        $ch = $this->getChannel();

        list($exchange, $routingKey) = $this->placeholderResolver->handlePlaceholdersParameters($exchange, $routingKey);

        if (!$routingKey) {
            $routingKey = $exchange;
            $exchange = '';
        }

        $ch->basic_publish($message, $exchange, $routingKey ?? $exchange);
    }

    /**
     * @param string             $queue
     * @param AMQPMessage|string $message
     */
    public function publishToQueue(string $queue, $message)
    {
        if (!$message instanceof AMQPMessage) {
            $message = new AMQPMessage($message, ['deliveryMode' => 2]);
        }

        list($queue) = $this->placeholderResolver->handlePlaceholdersParameters($queue);

        $this->getChannel()->basic_publish($message, '', $queue);
    }

    /**
     * @param        $exchange
     * @param Packet $packet
     * @param string $routingKey
     * @param array  $options
     */
    public function sendPacket($exchange, Packet $packet, string $routingKey = null, $options = [])
    {
        $packet->setAuthContext($this->securityManager->getAuthenticationInformationData());
        $packet->setReplyContext(array_merge($this->replyContext()->all(), $packet->getReplyContext() ?? []));

        $this->publishToExchange($exchange, json_encode($packet), $routingKey, $options);
    }
}