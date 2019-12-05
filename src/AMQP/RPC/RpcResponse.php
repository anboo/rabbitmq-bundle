<?php

namespace Anboo\ApiBundle\AMQP\RPC;

/**
 * Class RpcResponse
 */
class RpcResponse
{
    const RESPONSE_NONE = 'RESPONSE_NONE';

    /** @var array */
    private $responseData;

    /**
     * RpcResponse constructor.
     * @param array $responseData
     */
    public function __construct(array $responseData)
    {
        $this->responseData = $responseData;
    }

    /**
     * @param array $response
     * @return self
     */
    public static function create(array $response)
    {
        return new self($response);
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}