<?php

namespace Anboo\RabbitmqBundle\Consumer;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Anboo\RabbitmqBundle\AMQP\Exception\AmqpFatalException;
use Anboo\RabbitmqBundle\AMQP\Exception\AmqpNotFoundEntityException;
use Anboo\RabbitmqBundle\AMQP\Packet;
use Anboo\RabbitmqBundle\AMQP\Router\Route;
use Anboo\RabbitmqBundle\EventDispatcher\AMQP\AmqpBeforeProcess;
use Anboo\RabbitmqBundle\Service\CRUD\ReplaceItemService;
use Anboo\RabbitmqBundle\Service\DTO\DTOFactory;
use Webslon\Library\Api\Exception\ApiException;

class ReplaceConsumer extends AbstractCrudConsumer
{
    /**
     * @var string
     */
    protected $topicName = Route::TOPIC_REPLACE_PREFIX;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ReplaceItemService
     */
    private $replaceItemService;

    /**
     * @var DTOFactory
     */
    private $dtoFactory;

    /**
     * @required
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @required
     * @param DTOFactory $dtoFactory
     * @return $this
     */
    public function setDtoFactory(DTOFactory $dtoFactory): self
    {
        $this->dtoFactory = $dtoFactory;

        return $this;
    }

    /**
     * @required
     * @param ReplaceItemService $replaceItemService
     */
    public function setReplaceItemService(ReplaceItemService $replaceItemService): void
    {
        $this->replaceItemService = $replaceItemService;
    }

    /**
     * @param Packet $packet
     *
     * @return void
     * @throws ApiException
     * @throws \ReflectionException
     * @throws \Webslon\Library\Api\Service\HandlerException\Validation\ValidationException
     */
    public function doProcess(Packet $packet)
    {
        $request = new Request();
        $request->setMethod('PATCH');
        $this->replaceItemService->setRequest($request);

        $event = new AmqpBeforeProcess($packet, __CLASS__);
        $this->replaceItemService->getDependencies()->getDispatcher()->dispatch(AmqpBeforeProcess::AMQP_EVENT_BEFORE_PROCESS, $event);

        $entityId = $packet->getField('id');
        $updateData = $packet->getData();

        try {
            $this->entityManager->getMetadataFactory()->getMetadataFor($this->entityClass);
            $isEntity = true;
        } catch (MappingException $mappingException) {
            $isEntity = false;
        }

        if (!$isEntity) {
            $this->replaceItemService->setDtoClass($this->entityClass);

            $entityClassForDto = $this->dtoFactory->getEntityClassForDto($this->entityClass);
            if (!$entityClassForDto) {
                throw new AmqpFatalException('DTO class '.$this->entityClass.' must use DTO annotation');
            }

            $entityClass = $entityClassForDto;
        } else {
            $entityClass = $this->entityClass;
        }
        // create or replace (full update) entity
        $this->replaceItemService->replace($entityId, json_encode($updateData), $entityClass);

        $this->entityManager->clear();
        gc_collect_cycles();
    }
}