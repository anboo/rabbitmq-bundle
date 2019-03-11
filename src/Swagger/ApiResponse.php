<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 09.03.19
 * Time: 22:13
 */

namespace Anboo\ApiBundle\Swagger;

/**
 * Class ApiResponse
 */
class ApiResponse
{
    /**
     * @var object
     */
    private $data;

    /**
     * @var string
     */
    private $requestId;

    /**
     * @var boolean
     */
    private $status;

    /**
     * @var string[]
     */
    private $errors;

    /**
     * @param string $requestId
     * @param array $errors
     * @return ApiResponse
     */
    public static function createErrorResponse(string $requestId, array $errors)
    {
        $self = new self();

        $self->requestId = $requestId;
        $self->errors = $errors;
        $self->status = false;

        return $self;
    }

    /**
     * @param string $requestId
     * @param mixed  $data
     * @return ApiResponse
     */
    public static function createSuccessfullyResponse(string $requestId, $data)
    {
        $self = new self();

        $self->requestId = $requestId;
        $self->data = $data;
        $self->status = true;

        return $self;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}