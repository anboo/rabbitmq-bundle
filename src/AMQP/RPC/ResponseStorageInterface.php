<?php

namespace Anboo\RabbitmqBundle\AMQP\RPC;

/**
 * Interface ResponseStorageInterface
 */
interface ResponseStorageInterface
{
    public function storeResponse(string $requestId, array $response, int $timeout);
    public function getResponse(string $requestId);
    public function waitResponse(string $requestId);
}