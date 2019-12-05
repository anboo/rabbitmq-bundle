<?php

namespace Anboo\RabbitmqBundle\AMQP\RPC;

use Predis\Client;

/**
 * Class RedisResponseStorage
 */
class RedisResponseStorage implements ResponseStorageInterface
{
    /** @var Client */
    private $client;

    /** @var string */
    private $host;

    /** @var string */
    private $scheme;

    /** @var integer */
    private $port;

    /**
     * RedisResponseStorage constructor.
     * @param string $host
     * @param int $port
     * @param string $scheme
     */
    public function __construct(string $host, int $port, string $scheme = 'tcp')
    {
        $this->host = $host;
        $this->scheme = $scheme;
        $this->port = $port;
        $this->client = new Client([
            'scheme' => $scheme,
            'host' => $this->host,
            'port' => $this->port,
            'database' => 0,
            'read_write_timeout' => 0,
        ]);
    }

    /**
     * @param string $requestId
     * @param array $response
     * @param int|null $timeout
     */
    public function storeResponse(string $requestId, array $response, ?int $timeout)
    {
        $channel = $this->formRedisPublishSubscribeChannel($requestId);
        $key = $this->formResponseKeyFor($requestId);

        if ($timeout === 0) {
            $timeout = null;
        } elseif ($timeout / 1000 < 1) {
            $timeout = $timeout * 1000;
        }

        if ($timeout) {
            $this->client->set($key, json_encode($response), 'ex', $timeout);
        } else {
            $this->client->set($key, json_encode($response));
        }

        $this->client->rpush($channel, microtime(true));
    }

    /**
     * @param string $requestId
     * @return array
     */
    public function getResponse(string $requestId)
    {
        $data = $this->client->get(
            $this->formResponseKeyFor($requestId)
        );

        return json_decode($data, true);
    }

    /**
     * @param string $requestId
     */
    public function waitResponse(string $requestId)
    {
        $channel = $this->formRedisPublishSubscribeChannel($requestId);
        $this->client->lpop($channel);
    }

    /**
     * @param string $requestId
     * @return string
     */
    private function formRedisPublishSubscribeChannel(string $requestId)
    {
        return 'REQUEST_CH_'.$requestId;
    }

    /**
     * @param string $requestId
     * @return string
     */
    private function formResponseKeyFor(string $requestId)
    {
        return 'request_'.$requestId;
    }
}