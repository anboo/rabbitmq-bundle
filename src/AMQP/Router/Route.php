<?php

namespace Anboo\RabbitmqBundle\AMQP\Router;

use Anboo\RabbitmqBundle\Annotation\Enqueue\Consume;

/**
 * Class Route
 */
class Route
{
    const TOPIC_CREATE_PREFIX = 'create';
    const TOPIC_UPDATE_PREFIX = 'update';
    const TOPIC_REPLACE_PREFIX = 'replace';
    const TOPIC_DELETE_PREFIX = 'delete';

    /** @var string */
    private $name;

    /** @var string */
    private $queue;

    /** @var string|null */
    private $itemType;

    /** @var string|null */
    private $exchangeName;

    /** @var string|null */
    private $exchangeBindKey;

    /** @var string */
    private $consumer;

    /** @var string */
    private $action;

    /** @var array */
    private $exchangeParameters;

    /** @var array */
    private $queueParameters;

    /** @var array */
    private $consumerParameters;

    /** @var array|null */
    private $onErrors;

    /**
     * Route constructor.
     * @param string     $name
     * @param string     $queue
     * @param string     $itemType
     * @param string     $exchangeName
     * @param string     $exchangeBindKey
     * @param string     $consumer
     * @param string     $action
     * @param array|null $onErrors
     */
    public function __construct(string $name, string $queue, ?string $itemType, ?string $exchangeName, ?string $exchangeBindKey, string $consumer, string $action, ?array $onErrors = null)
    {
        $this->name = $name;
        $this->queue = $queue;
        $this->itemType = $itemType;
        $this->exchangeName = $exchangeName;
        $this->exchangeBindKey = $exchangeBindKey;
        $this->consumer = $consumer;
        $this->action = $action;
        $this->onErrors = $onErrors;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return string|null
     */
    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    /**
     * @return string
     */
    public function getConsumer(): string
    {
        return $this->consumer;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getOnErrors(): ?string
    {
        return $this->onErrors;
    }

    /**
     * @return string|null
     */
    public function getExchangeName(): ?string
    {
        return $this->exchangeName;
    }

    /**
     * @return string|null
     */
    public function getExchangeBindKey(): ?string
    {
        return $this->exchangeBindKey;
    }

    /**
     * @return array
     */
    public function getExchangeParameters(): array
    {
        return $this->exchangeParameters;
    }

    /**
     * @param array $exchangeParameters
     * @return $this
     */
    public function setExchangeParameters(array $exchangeParameters): self
    {
        $this->exchangeParameters = $exchangeParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getQueueParameters(): array
    {
        return $this->queueParameters;
    }

    /**
     * @param array $queueParameters
     * @return $this
     */
    public function setQueueParameters(array $queueParameters): self
    {
        $this->queueParameters = $queueParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getConsumerParameters(): array
    {
        return $this->consumerParameters;
    }

    /**
     * @param array $consumerParameters
     * @return $this
     */
    public function setConsumerParameters(array $consumerParameters): self
    {
        $this->consumerParameters = $consumerParameters;

        return $this;
    }

    /**
     * @param Consume $consume
     * @param string $serviceId
     * @param string $reflectionMethod
     * @return array
     */
    public static function createFromConsume(Consume $consume, string $serviceId, string $action)
    {
        return [
            'name' => $consume->queue,
            'queue' => $consume->queue,
            'onErrors' => $consume->onErrors,
            'itemType' => null,
            'consumer' => $serviceId,
            'action' => $action,
            'exchange_bind_key' => $consume->exchangeBindKey,
            'exchange_name' => $consume->exchangeName,
            'exchange_parameters' => [
                'type' => $consume->exchangeParameters->type,
                'internal' => $consume->exchangeParameters->internal,
                'autoDelete' => $consume->exchangeParameters->autoDelete,
                'passive' => $consume->exchangeParameters->passive,
                'durable' => $consume->exchangeParameters->durable,
                'arguments' => $consume->exchangeParameters->arguments,
            ],
            'queue_parameters' => [
                'exclusive' => $consume->queueParameters->exclusive,
                'autoDelete' => $consume->queueParameters->autoDelete,
                'passive' => $consume->queueParameters->passive,
                'durable' => $consume->queueParameters->durable,
                'arguments' => $consume->queueParameters->arguments,
            ],
            'consumer_parameters' => [
                'noLocal' => $consume->consumerParameters->noLocal,
                'arguments' => $consume->consumerParameters->arguments,
                'exclusive' => $consume->consumerParameters->exclusive,
                'noAck' => $consume->consumerParameters->noAck,
                'noWait' => $consume->consumerParameters->noWait,
            ],
        ];
    }
}