<?php

namespace Anboo\ApiBundle\AMQP;

use DateTime;
use Exception;
use JsonSerializable;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Webslon\Library\Api\Exception\ApiException;

/**
 * Class Packet
 */
class Packet implements JsonSerializable
{
    const RPC_ASYNC = 'async';
    const RPC_SYNC = 'sync';
    const RPC_NONE = 'none';

    /** @var integer|string|null */
    private $id;

    /** @var DateTime */
    private $date;

    /** @var array */
    private $data;

    /** @var ApiException[]|null */
    private $errors;

    /** @var array|null */
    private $replyContext;

    /** @var string|null */
    private $rpc;

    /** @var array */
    private $authContext = [];

    /** @var array */
    private $rpcCallStack = [];

    /** @var AMQPMessage|null */
    private $AMQPMessage;

    /**
     * Packet constructor.
     * @param int|string $id
     * @param DateTime $date
     * @param array $data
     * @param string $rpc
     * @param array $replyContext
     * @param array $errors
     */
    public function __construct ($id, DateTime $date, array $data = [], $rpc = self::RPC_NONE, ?array $errors = null, ?array $replyContext = null)
    {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->date = $date;
        $this->data = $data;
        $this->rpc = $rpc;
        $this->errors = $errors;
        $this->replyContext = $replyContext;
    }

    /**
     * @param array $data
     * @param string $rpc
     * @param array|null $errors
     * @return Packet
     */
    public static function createFromData(array $data, $rpc = self::RPC_NONE, ?array $errors = null)
    {
        return new self(Uuid::uuid4()->toString(), new \DateTime(), $data, $rpc, $errors);
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return ApiException[]|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * @return array|null
     */
    public function getReplyContext(): ?array
    {
        return $this->replyContext;
    }

    /**
     * @param ApiException $error
     * @return Packet
     */
    public function addError(ApiException $error): Packet
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @param ApiException[]|null $errors
     * @return $this
     */
    public function setErrors(array $errors): self
    {
        $this->errors = [];

        foreach ($errors as $error) {
            $this->errors[] = is_scalar($error) ? new ApiException($error) : $error;
        }

        return $this;
    }

    /**
     * @param $field
     * @return mixed|null
     */
    public function getField($field)
    {
        if (isset($this->data[$field])) {
            return $this->data[$field];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isSyncRPC()
    {
        return self::RPC_SYNC === $this->rpc;
    }

    /**
     * @return bool
     */
    public function isAsyncRPC()
    {
        return self::RPC_ASYNC === $this->rpc;
    }

    /**
     * @return string|null
     */
    public function getRpc(): ?string
    {
        return $this->rpc;
    }

    /**
     * @param string|null $rpc
     * @return Packet
     */
    public function setRpc(?string $rpc): self
    {
        $this->rpc = $rpc;
        return $this;
    }

    /**
     * @return AMQPMessage|null
     */
    public function getAMQPMessage(): ?AMQPMessage
    {
        return $this->AMQPMessage;
    }

    /**
     * @param AMQPMessage $AMQPMessage
     */
    public function setAMQPMessage(AMQPMessage $AMQPMessage)
    {
        $this->AMQPMessage = $AMQPMessage;
    }

    /**
     * @param DateTime $date
     * @return $this
     */
    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array|null $replyContext
     * @return $this
     */
    public function setReplyContext(?array $replyContext): self
    {
        $this->replyContext = $replyContext;

        return $this;
    }

    /**
     * @return array
     */
    public function getAuthContext(): ?array
    {
        return $this->authContext;
    }

    /**
     * @param array $authContext
     * @return $this
     */
    public function setAuthContext(?array $authContext): self
    {
        $this->authContext = $authContext;

        return $this;
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     */
    public function getRpcCallStack(): array
    {
        return $this->rpcCallStack;
    }

    /**
     * @param array $rpcCallStack
     * @return $this
     */
    public function setRpcCallStack(array $rpcCallStack): self
    {
        $this->rpcCallStack = $rpcCallStack;

        return $this;
    }

    /**
     * @param array $rpcCallStack
     * @return $this
     */
    public function addRpcCallStack(array $rpcCallStack): self
    {
        $this->rpcCallStack[] = $rpcCallStack;

        return $this;
    }

    /**
     * @return array
     */
    public function popRpcCallStack(): array
    {
        if (!$this->rpcCallStack) {
            return [];
        }

        return array_pop($this->rpcCallStack);
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     */
    public function jsonSerialize()
    {
        $errors = [];
        foreach ($this->getErrors() ?? [] as $error) {
            if (is_array($error)) {
                $error = new ApiException($error['message'], 'Error_'.$error['code'], null, $error['code']);
            }
            $traceAsString = $error instanceof ApiException ? $error->getErrorTrace() : $error->getTraceAsString();
            $trace = getenv('APP_ENV') !== 'prod' ? $traceAsString : null;
            if ($error instanceof ApiException) {
                $errors[] = [
                    'type' => $error->getType(),
                    'message' => $error->getMessage(),
                    'code' => $error->getCode(),
                    'stringCode' => $error->getStringCode(),
                    'trace' => $trace,
                ];
            } else {
                $code = Response::$statusTexts[$error->getCode()] ? $error->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
                $errors[] = [
                    'type' => (new \ReflectionClass($error))->getName(),
                    'message' => $error->getMessage(),
                    'code' => $code,
                    'stringCode' => 'Error_'.$code,
                    'trace' => $trace,
                ];
            }
        }

        return [
            'id' => $this->id,
            'date' => $this->date ? $this->date->format('d.m.Y H:i:s') : null,
            'data' => $this->data,
            'errors' => $errors,
            'replyContext' => $this->getReplyContext() ?? [],
            'rpc' => $this->rpc,
            'authContext' => $this->authContext,
            'rpcCallStack' => $this->rpcCallStack,
        ];
    }
}