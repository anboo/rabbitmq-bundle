<?php

namespace Anboo\RabbitmqBundle\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use Anboo\RabbitmqBundle\AMQP\Packet;

/**
 * Class AbstractProducer
 */
abstract class AbstractConsumer extends Consumer implements WebslonConsumer
{
    protected $entityClass;

    /**
     * AbstractProducer constructor.
     *
     * @param string $entityClass
     */
    public function __construct(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param Packet $packet
     *
     * @return object|string
     */
    public abstract function process(Packet $packet);
}