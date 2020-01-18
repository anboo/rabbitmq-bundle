<?php


namespace Anboo\RabbitmqBundle\Event;

use Anboo\RabbitmqBundle\AMQP\Packet;
use Symfony\Contracts\EventDispatcher\Event;

class PostProcessMessageEvent extends Event
{
    const NAME = 'anboo.rabbitmq_bundle.post_process_message_event';

    /** @var Packet */
    private $packet;

    /**
     * PostProcessMessage constructor.
     * @param Packet $packet
     */
    public function __construct(Packet $packet)
    {
        $this->packet = $packet;
    }

    /**
     * @return Packet
     */
    public function getPacket(): Packet
    {
        return $this->packet;
    }
}