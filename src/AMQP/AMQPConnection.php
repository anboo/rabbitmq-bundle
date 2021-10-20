<?php

namespace Anboo\RabbitmqBundle\AMQP;

use PhpAmqpLib\Connection\AMQPLazyConnection;

/**
 * Class AMQPConnection
 */
class AMQPConnection extends AMQPLazyConnection
{
    public function __construct(
        $host,
        $port,
        $user,
        $password,
        $vhost = '/',
        $insist = false,
        $login_method = 'AMQPLAIN',
        $login_response = null,
        $locale = 'en_US',
        $connection_timeout = 3.0,
        $read_write_timeout = 3.0,
        $context = null,
        $keepalive = false,
        $heartbeat = 0,
        $channel_rpc_timeout = 0.0,
        $ssl_protocol = null
    ) {
        $parsedDsn = parse_url($_ENV['MESSENGER_TRANSPORT_DSN']);
        if ($parsedDsn) {
            $host = $parsedDsn['host'];
            $port = $parsedDsn['port'];
            $user = $parsedDsn['user'];
            $password = $parsedDsn['pass'];
        }

        parent::__construct(
            $host,
            $port,
            $user,
            $password,
            $vhost,
            $insist,
            $login_method,
            $login_response,
            $locale,
            $connection_timeout,
            $read_write_timeout,
            $context,
            $keepalive,
            $heartbeat,
            $channel_rpc_timeout,
            $ssl_protocol
        );
    }
}
