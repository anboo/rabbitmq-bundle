<?php

namespace Anboo\RabbitmqBundle\Consumer;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Anboo\RabbitmqBundle\AMQP\Packet;
use Anboo\RabbitmqBundle\AMQP\Producer;
use Anboo\RabbitmqBundle\AMQP\RPC\Exchange;
use Anboo\RabbitmqBundle\AMQP\RPC\ReplyContextTrait;

/**
 * Class Consumer
 */
abstract class Consumer
{
    use ReplyContextTrait;

    /** @var Packet */
    private $requestPacket;

    /** @var Producer */
    protected $producer;

    /** @var AMQPMessage */
    private $amqpMessage;

    /**
     * @required
     * @param Producer $producer
     */
    public function setProducer(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param Packet $requestPacket
     * @return $this
     */
    public function setRequestPacket(Packet $requestPacket): self
    {
        $this->requestPacket = $requestPacket;
        $this->amqpMessage = $this->requestPacket->getAMQPMessage();

        return $this;
    }

    /**
     * @param boolean $requeue
     * @return $this
     */
    public function reject(bool $requeue = true) : self
    {
        /** @var AMQPChannel $ch */
        $ch = $this->amqpMessage->delivery_info['channel'];
        $ch->basic_reject($this->amqpMessage->delivery_info['delivery_tag'], $requeue);

        return $this;
    }

    /**
     * @return $this
     */
    public function ack() : self
    {
        /** @var AMQPChannel $ch */
        $ch = $this->amqpMessage->delivery_info['channel'];
        $ch->basic_ack($this->amqpMessage->delivery_info['delivery_tag']);

        return $this;
    }

    /**
     * @return $this
     */
    public function nack() : self
    {
        /** @var AMQPChannel $ch */
        $ch = $this->amqpMessage->delivery_info['channel'];
        $ch->basic_nack($this->amqpMessage->delivery_info['delivery_tag']);

        return $this;
    }

    /**
     * @param array $response
     * @param array $replyContext
     * @return $this
     */
    public function reply(array $response, array $replyContext = []) : self
    {
        $responsePacket = clone $this->requestPacket;
        $responsePacket->setData($response);
        $responsePacket->setDate(new \DateTime());

        $this->replyProcess($responsePacket, $replyContext);

        return $this;
    }

    /**
     * @param array $errors
     * @param array $replyContext
     * @return $this
     */
    public function replyErrors(array $errors, array $replyContext = []) : self
    {
        $responsePacket = clone $this->requestPacket;
        $responsePacket->setErrors($errors);
        $responsePacket->setDate(new \DateTime());

        $this->replyProcess($responsePacket, $replyContext);

        return $this;
    }

    /**
     * @param $error
     * @param array $replyContext
     * @return Consumer
     */
    public function replyError($error, $replyContext = []) : self
    {
        return $this->replyErrors([$error], $replyContext);
    }

    /**
     * @return string|null
     */
    public function getClientId() : ?string
    {
        return $this->requestPacket->getAuthContext()['clientId'] ?? null;
    }

    public function rpcForwardAsync(string $exchange, array $message, string $routingKey = null, $replyTo = null, array $options = [])
    {
        $correlationId = $this->requestPacket->getId();

        if ($replyTo instanceof Exchange) {
            $replyToExchange = $replyTo->getExchange();
            $replyToRoutingKey = $replyTo->getRoutingKey();
        } else {
            $replyToExchange = '';
            $replyToRoutingKey = $replyTo;
        }

        $rpcCallStack = ['exchange' => $replyToExchange, 'routing_key' => $replyToRoutingKey];
        $preparedPacket = $this->requestPacket->addRpcCallStack($rpcCallStack);
        $preparedPacket->setData($message);

        $this->producer->replyContext()->load($this->replyContext()->all());
        $this->producer->rpcCallAsync(new Exchange($exchange, $routingKey), $preparedPacket, $correlationId, $replyTo, $options);
    }

    /**
     * @return string|null
     */
    public function getAccessToken() : ?string
    {
        return $this->requestPacket->getAuthContext()['accessToken'] ?? null;
    }

    /**
     * @return array|null
     */
    public function getRoles() : ?array
    {
        return $this->requestPacket->getAuthContext()['user']['roles'] ?? null;
    }

    /**
     * @param array $replyContext
     */
    public function loadReplyContext(array $replyContext)
    {
        $this->replyContext()->load($replyContext);
    }

    /**
     * @param Packet $responsePacket
     * @param array  $replyContext
     */
    private function replyProcess(Packet $responsePacket, array $replyContext)
    {
        list($exchange, $routingKey) = $this->getReplyData($responsePacket);

        if(!$exchange && !$routingKey) {
            throw new \RuntimeException('Cannot reply if request AMQP message haven\'t reply_to.exchange, reply_to.routing_key or reply_to or rpcCallStack data');
        }

        $replyContextAll = array_merge($this->replyContext()->all(), $replyContext);
        $responsePacket->setReplyContext($replyContextAll);
        $this->producer->sendPacket($exchange, $responsePacket, $routingKey);
    }

    /**
     * @param Packet $responsePacket
     *
     * @return array
     */
    protected function getReplyData(Packet $responsePacket)
    {
        $exchange = $routingKey = null;
        if ($this->amqpMessage->has('application_headers')) {
            $applicationHeaders = $this->amqpMessage->get('application_headers');
            if ($applicationHeaders instanceof \Traversable) {
                $headers = iterator_to_array($this->amqpMessage->get('application_headers'));
                if (
                    isset($headers['reply_to.exchange']) &&
                    isset($headers['reply_to.exchange'][1]) &&
                    isset($headers['reply_to.routing_key']) &&
                    isset($headers['reply_to.routing_key'][1])
                ) {
                    $exchange = $headers['reply_to.exchange'][1];
                    $routingKey = $headers['reply_to.routing_key'][1];
                }
            }
        }

        if ($this->amqpMessage->has('reply_to')) {
            $exchange = '';
            $routingKey = $this->amqpMessage->get('reply_to');
        }

        $lastStack = $responsePacket->popRpcCallStack();
        if (is_null($exchange) || is_null($routingKey)) {
            if ($lastStack) {
                $exchange = $lastStack['exchange'];
                $routingKey = $lastStack['routing_key'];
            }
        }

        return [$exchange, $routingKey];
    }
}