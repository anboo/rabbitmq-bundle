<?php

namespace Anboo\RabbitmqBundle\Monolog;

use PhpAmqpLib\Message\AMQPMessage;
use Anboo\RabbitmqBundle\AMQP\Packet;
use Anboo\RabbitmqBundle\Command\ConsumerCommand;
use \Exception;

class RabbitMqContext
{
    /**
     * @param Packet $packet
     * @param Exception $e
     * @return array
     */
    public static function getLoggingContext(Packet $packet, $e = null)
    {
        return self::getLoggingContextAmqpMessage($packet->getAMQPMessage(), $e);
    }

    /**
     * @param AMQPMessage $AMQPMessage
     * @param Exception $e
     * @return array
     */
    public static function getLoggingContextAmqpMessage(AMQPMessage $AMQPMessage, $e = null) : array
    {
        $context = [
            'tags' => [
                'exchange' => $AMQPMessage->delivery_info['exchange'] ?? null,
                'routing_key' => $AMQPMessage->delivery_info['routing_key'] ?? null,
                'command' => ConsumerCommand::getDefaultName(),
            ],
            'body' => $AMQPMessage->getBody() ?? null,
            'exception' => $e,
        ];

        return array_filter($context, function($item) { return $item !== null || $item === 0; });
    }
}