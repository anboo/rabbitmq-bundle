<?php

namespace Anboo\ApiBundle\AMQP\RPC;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class RpcManager
 */
class RpcManager
{
    /** @var string */
    private $prefixResponseCallbackQueueName;

    /** @var string */
    private $lifetimeCallbackRpcQueue;

    /**
     * RpcManager constructor.
     *
     * @param string $prefixResponseCallbackQueueName
     * @param string $lifetimeCallbackRpcQueue
     */
    public function __construct(string $prefixResponseCallbackQueueName, string $lifetimeCallbackRpcQueue)
    {
        $this->prefixResponseCallbackQueueName = $prefixResponseCallbackQueueName;
        $this->lifetimeCallbackRpcQueue = $lifetimeCallbackRpcQueue;
    }

    /**
     * @param string $correlationId
     * @return string
     */
    public function getRpcCallbackQueueName(string $correlationId) : string
    {
        return sprintf('%s.%s', $this->prefixResponseCallbackQueueName, $correlationId);
    }

    /**
     * @param AMQPChannel $channel
     * @param string $correlationId
     * @return string
     */
    public function declareRpcCallbackQueue(AMQPChannel $channel, string $correlationId) : string
    {
        $queueForFrontend = $this->getRpcCallbackQueueName($correlationId);

        $arguments = new AMQPTable(['x-expires' => $this->lifetimeCallbackRpcQueue * 1000]);
        $channel->queue_declare($queueForFrontend, false, true, false, false, false, $arguments);

        return $queueForFrontend;
    }
}