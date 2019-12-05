<?php

namespace Anboo\RabbitmqBundle\AMQP;

use Ramsey\Uuid\Uuid;
use Anboo\RabbitmqBundle\AMQP\Router\Route;
use Anboo\RabbitmqBundle\AMQP\Router\RouterCollection;
use Anboo\RabbitmqBundle\AMQP\RPC\ResponseStorageInterface;

/**
 * Class BaseClient
 */
class BaseProducer extends Producer
{
    /**
     * @var array
     */
    private $topicsMap;

    /**
     * BaseClient constructor.
     * @param array $topicsMap
     * @param ResponseStorageInterface $responseStorage
     */
    public function __construct(array $topicsMap)
    {
        $this->topicsMap = $topicsMap;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $packet = new Packet(null, new \DateTime(), ['id' => $id]);

        $this->sendPacket(
            $this->getTopicRouteOrFail(Route::TOPIC_DELETE_PREFIX),
            $packet
        );
    }

    /**
     * @param array $data
     */
    public function create(array $data)
    {
        $packet = new Packet(null, new \DateTime(), $data);

        $this->sendPacket(
            $this->getTopicRouteOrFail(Route::TOPIC_CREATE_PREFIX),
            $packet
        );
    }

    /**
     * @param $id
     * @param array $data
     */
    public function update($id, array $data)
    {
        $packet = new Packet(null, new \DateTime(), ['id' => $id, 'data' => $data]);

        $this->sendPacket(
            $this->getTopicRouteOrFail(Route::TOPIC_UPDATE_PREFIX),
            $packet
        );
    }

    /**
     * @param string $topicMethod
     * @throws \RuntimeException
     * @return string
     */
    private function getTopicRouteOrFail($topicMethod)
    {
        if (!isset($this->topicsMap[$topicMethod])) {
            throw new \RuntimeException(sprintf('AMQP method %s not configured in %s', $topicMethod, static::class));
        }

        return $this->topicsMap[$topicMethod];
    }
}