<?php

namespace Anboo\RabbitmqBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use ParagonIE\Paseto\Exception\PasetoException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Sentry\SentryBundle\SentrySymfonyClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Anboo\RabbitmqBundle\AMQP\AMQPConnection;
use Anboo\RabbitmqBundle\AMQP\Model\Bind;
use Anboo\RabbitmqBundle\AMQP\Packet;
use Anboo\RabbitmqBundle\AMQP\RabbitRestClient;
use Anboo\RabbitmqBundle\AMQP\Router\RouterCollection;
use Anboo\RabbitmqBundle\AMQP\RPC\ResponseStorageInterface;
use Anboo\RabbitmqBundle\AMQP\RPC\RpcResponse;
use Anboo\RabbitmqBundle\Consumer\Consumer;
use Anboo\RabbitmqBundle\Monolog\RabbitMqContext;
use Webslon\Bundle\AuthBundle\Security\Encryptor;
use Webslon\Bundle\AuthBundle\Security\SecurityManager;
use Webslon\Bundle\AuthBundle\Security\Token\AmqpPreAuthenticatedToken;
use Webslon\Library\Logger\Client\SentryClient;
use Anboo\RabbitmqBundle\EventDispatcher\AMQP\RejectEvent;


class ConsumerCommand extends Command
{
    const BEFORE_REJECT = 'amqp.onBeforeReject';

    protected static $defaultName = 'webslon:api:consumer';

    /**
     * @var AMQPConnection
     */
    private $connection;

    /**
     * @var RouterCollection
     */
    private $routeCollection;

    /**
     * @var ResponseStorageInterface
     */
    private $responseStorage;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RabbitRestClient
     */
    private $rabibtmqClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $processedMessages = 0;

    /**
     * @var int|null
     */
    private $maxMessages;

    /**
     * @var string[]
     */
    private $supportedQueues;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * ConsumerCommand constructor.
     *
     * @param AMQPConnection $connection
     * @param RabbitRestClient $rabibtmqClient
     * @param ResponseStorageInterface $responseStorage
     * @param RouterCollection $routeCollection
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct (
        AMQPConnection $connection,
        RabbitRestClient $rabibtmqClient,
        ResponseStorageInterface $responseStorage,
        RouterCollection $routeCollection,
        ContainerInterface $container,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->rabibtmqClient = $rabibtmqClient;
        $this->responseStorage = $responseStorage;
        $this->routeCollection = $routeCollection;
        $this->container = $container;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('setup-broker', 's', InputOption::VALUE_NONE, 'Setup broker')
            ->addOption('max-messages', 'm', InputOption::VALUE_REQUIRED, 'Max messages limit')
            ->addOption('queue', 'qq', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Start consuming queue')
        ;
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        gc_enable();
        $this->maxMessages = $input->getOption('max-messages');
        $this->supportedQueues = $input->getOption('queue');

        $ch = $this->connection->channel();

        $ch->basic_qos(null, 1, null);

        $queueDeclares = $exchangeDeclares = $exchangeBindings = $exchangeBindingsAlreadyExists = [];
        $routes = $this->routeCollection->all();

        foreach ($this->routeCollection->all() as $route) {
            if ($input->getOption('setup-broker')) {
                $queueDeclares[] = $route->getQueue();

                if ($route->getExchangeName() && $route->getExchangeBindKey()) {
                    $exchangeDeclares[] = $route->getExchangeName();
                    $exchangeBindings[] = [
                        'queue' => $route->getQueue(),
                        'exchange' => $route->getExchangeName(),
                        'binding_key' => $route->getExchangeBindKey(),
                    ];
                }
            }
        }

        foreach ($routes as $route) {
            $queueDeclare = $route->getQueue();
            $queueParameters = $route->getQueueParameters();

            $io->comment(sprintf('Declare queue %s', $queueDeclare));
            $ch->queue_declare(
                $queueDeclare,
                $queueParameters['passive'],
                $queueParameters['durable'],
                $queueParameters['exclusive'],
                $queueParameters['autoDelete'],
                false,
                !empty($exchangeParameters['arguments']) ? new AMQPTable($exchangeParameters['arguments']) : []
            );

            if ($this->isQueueSupport($queueDeclare)) {
                $io->comment(sprintf('Start queue %s', $queueDeclare));

                $consumerParameters = $route->getConsumerParameters();

                $consumerTag = php_uname('n').'@'.uniqid();
                $callback = function(AMQPMessage $message) use ($queueDeclare) {
                    $this->process($message, $queueDeclare);
                };

                $ch->basic_consume(
                    $queueDeclare,
                    $consumerTag,
                    $consumerParameters['noLocal'],
                    $consumerParameters['noAck'],
                    $consumerParameters['exclusive'],
                    $consumerParameters['noWait'],
                    $callback
                );
            }
        }

        foreach ($routes as $route) {
            $exchangeDeclare = $route->getExchangeName();
            $exchangeParameters = $route->getExchangeParameters();

            if ($route->getExchangeName() && $route->getExchangeBindKey()) {
                $io->comment(sprintf('Declare exchange %s', $exchangeDeclare));
                $ch->exchange_declare(
                    $exchangeDeclare,
                    $exchangeParameters['type'],
                    $exchangeParameters['passive'],
                    $exchangeParameters['durable'],
                    $exchangeParameters['autoDelete'],
                    $exchangeParameters['internal'],
                    false,
                    !empty($exchangeParameters['arguments']) ? new AMQPTable($exchangeParameters['arguments']) : []
                );
            }
        }

        foreach ($routes as $route) {
            if ($route->getExchangeName() && $route->getExchangeBindKey()) {
                $io->comment(sprintf(
                    'Bind queue %s to exchange %s by key %s',
                    $route->getQueue(),
                    $route->getExchangeName(),
                    $route->getExchangeBindKey()
                ));

                $ch->queue_bind($route->getQueue(), $route->getExchangeName(), $route->getExchangeBindKey());
            }
        }

        while (count($ch->callbacks)) {
            $ch->wait();
        }

        $ch->close();
        $this->connection->close();
    }

    /**
     * @param AMQPMessage $msg
     * @param string      $queueName
     *
     * @throws ORMException
     */
    public function process(AMQPMessage $msg, string $queueName)
    {
        /** @var AMQPChannel $msgChannel */
        $msgChannel = $msg->delivery_info['channel'];

        $body = $msg->getBody();
        $dataMsg = json_decode($body, true);

        if (!is_array($dataMsg)) {
            $msgChannel->basic_reject($msg->delivery_info['delivery_tag'], false);
            $this->logger->error('AMQP Message, expected json string', RabbitMqContext::getLoggingContextAmqpMessage($msg));
            return;
        }

        if (!isset($dataMsg['data']) || !is_array($dataMsg['data'])) {
            $msgChannel->basic_reject($msg->delivery_info['delivery_tag'], false);
            $this->logger->error('Expected "data" field array', RabbitMqContext::getLoggingContextAmqpMessage($msg));
            return;
        }

        if (!isset($dataMsg['date'])) {
            $dataMsg['date'] = new \DateTime();
        } else {
            $dataMsg['date'] = new \DateTime($dataMsg['date']);
        }

        $replyContext = isset($dataMsg['replyContext']) ? $dataMsg['replyContext'] : [];

        $packet = new Packet(
            $dataMsg['id'],
            $dataMsg['date'],
            $dataMsg['data'],
            isset($dataMsg['rpc']) ? $dataMsg['rpc'] : Packet::RPC_NONE,
            isset($dataMsg['errors']) ? $dataMsg['errors'] : null,
            $replyContext
        );
        $packet->setAMQPMessage($msg);

        if (isset($dataMsg['rpcCallStack'])) {
            $packet->setRpcCallStack($dataMsg['rpcCallStack'] ?? []);
        }

        $router = $this->routeCollection->getRouteByQueueName($queueName);
        $processorId = $router->getConsumer();
        $method = $router->getAction();

        try {
            $processor = $this->container->get($processorId);
            if ($processor instanceof Consumer) {
                $processor->setRequestPacket($packet);
                $processor->loadReplyContext($replyContext);
            }
            call_user_func([$processor, $method], $packet);
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('AMQP rejected, error: %s', $exception->getMessage()), RabbitMqContext::getLoggingContext($packet, $exception));
            return;
        }

        $this->processedMessages += 1;

        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        gc_collect_cycles();

        if ($this->maxMessages && $this->processedMessages >= $this->maxMessages) {
            $this->logger->notice(sprintf('Processed maximum messages %d', $this->processedMessages));
            $msgChannel->close();
            exit(0); //Successfully exit this process
        }
    }

    /**
     * @param string $queue
     * @return bool
     */
    private function isQueueSupport(string $queue) : bool
    {
        return empty($this->supportedQueues) || in_array($queue, $this->supportedQueues);
    }
}