<?php

namespace Anboo\ApiBundle\AMQP\RPC;

/**
 * Class FileSystemResponseStorage
 */
class FileSystemResponseStorage implements ResponseStorageInterface
{
    public function storeResponse(string $requestId, array $response, int $timeout)
    {
        file_put_contents($this->generateFileName($requestId), json_encode($response));
    }

    public function getResponse(string $requestId)
    {
        return json_decode(file_get_contents($this->generateFileName($requestId)), true);
    }

    public function waitResponse(string $requestId)
    {
        while (!file_exists($this->generateFileName($requestId))) {
            sleep(1);
        }

        return true;
    }

    private function generateFileName(string $requestId)
    {
        return '/tmp/request_'.$requestId;
    }
}