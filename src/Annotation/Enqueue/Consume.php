<?php

namespace Anboo\RabbitmqBundle\Annotation\Enqueue;

use Anboo\RabbitmqBundle\AMQP\Producer;

/**
 * Class Route
 *
 * @Annotation
 */
class Consume
{
    /**
     * @var string
     */
    public $queue;

    /**
     * @var string|null
     */
    public $exchangeBindKey;

    /**
     * @var string|null
     */
    public $exchangeName;

    /**
     * @var ExchangeParameters
     */
    public $exchangeParameters;

    /**
     * @var QueueParameters
     */
    public $queueParameters;

    /**
     * @var ConsumerParameters
     */
    public $consumerParameters;

    /**
     * @var Produce|null
     */
    public $onErrors;

    /**
     * Consume constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $k => $value) {
            $this->{$k} = $value;
        }

        if (!$this->exchangeParameters) {
            $this->exchangeParameters = new ExchangeParameters();
        }

        if (!$this->queueParameters) {
            $this->queueParameters = new QueueParameters();
        }

        if (!$this->consumerParameters) {
            $this->consumerParameters = new ConsumerParameters();
        }
    }
}