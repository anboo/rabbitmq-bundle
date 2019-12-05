<?php


namespace Anboo\ApiBundle\AMQP\Exception;

use Symfony\Component\HttpFoundation\Response;
use Webslon\Library\Api\Exception\ApiException;

/**
 * Class AmqpNotFoundProcessEntityException
 */
class AmqpNotFoundEntityException extends ApiException
{
    private $entityId;

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param mixed $entityId
     *
     * @return AmqpNotFoundEntityException
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }
}