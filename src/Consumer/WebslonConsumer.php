<?php

namespace Anboo\RabbitmqBundle\Consumer;

use Enqueue\Client\ProducerInterface;
use Interop\Queue\Processor;
use PhpAmqpLib\Message\AMQPMessage;
use Anboo\RabbitmqBundle\AMQP\Packet;

/**
 * Interface WebslonProducer
 */
interface WebslonConsumer
{
    /**
     * @param Packet $packet
     * @return object|string
     */
    public function process(Packet $packet);
}