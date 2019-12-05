<?php

namespace Anboo\RabbitmqBundle\AMQP;

use GuzzleHttp\Client;
use Anboo\RabbitmqBundle\AMQP\Model\Bind;

/**
 * Class RabbitRestClient
 */
class RabbitRestClient
{
    /** @var string */
    private $host;

    /** @var boolean */
    private $protocol;

    /** @var string */
    private $vhost;

    /** @var string */
    private $apiPort;

    /** @var string */
    private $username;

    /** @var string */
    private $password;
    /**
     * @var array
     */
    private $body;
    /**
     * @var array
     */
    private $headers;

    /**
     * RabbitRestClient constructor.
     * @param string $host
     * @param string $vhost
     * @param string $protocol
     * @param string $apiPort
     * @param string $username
     * @param string $password
     */
    public function __construct (string $host, string $vhost, string $protocol, string $apiPort, string $username, string $password)
    {
        $this->host = $host;
        $this->vhost = $vhost;
        $this->protocol = $protocol;
        $this->apiPort = $apiPort;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param string $exchangeName
     * @return Bind[]
     */
    public function getBindingsForExchange(string $exchangeName)
    {
        if (!$this->vhost || $this->vhost = '/') {
            $vhost = '%2f';
        } else {
            $vhost = $this->vhost;
        }

        $endpointUrl = '/api/exchanges/'.$vhost.'/'.$exchangeName.'/bindings/source';
        $bindingsData = $this->request('GET', $endpointUrl);

        $bindings = [];
        foreach ($bindingsData as $binding) {
            $bindings[] = new Bind(
                $binding['source'],
                $binding['destination'],
                $binding['destination_type'],
                $binding['routing_key'],
                $binding['properties_key'],
                $binding['arguments'],
                $binding['vhost']
            );
        }

        return $bindings;
    }

    /**
     * @param string $method
     * @param string $uri
     * @return array
     */
    public function request($method, $uri)
    {
        $client = new Client([
            'base_uri' => $this->protocol.'://'.$this->host.':'.$this->apiPort,
            'auth' => [$this->username, $this->password],
        ]);

        $response = $client->request($method, $uri, [
            'json' => $this->body,
        ])->getBody()->getContents();
        if (!$response) {
            throw new \RuntimeException('RabbitMQ response incorrect');
        }

        return json_decode($response, true);
    }

    /**
     * @return array
     */
    public function getBody():array
    {
        return $this->body;
    }

    /**
     * @param array $body
     *
     * @return RabbitRestClient
     */
    public function setBody(array $body):RabbitRestClient
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders():array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return RabbitRestClient
     */
    public function setHeaders(array $headers):RabbitRestClient
    {
        $this->headers = $headers;

        return $this;
    }
}
