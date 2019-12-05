<?php

namespace Anboo\ApiBundle\Consumer;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Anboo\ApiBundle\AMQP\Exception\AmqpFatalException;
use Anboo\ApiBundle\AMQP\Exception\AmqpNotFoundEntityException;
use Anboo\ApiBundle\AMQP\Packet;
use Anboo\ApiBundle\AMQP\Router\Route;
use Anboo\ApiBundle\Annotation\DTO;
use Anboo\ApiBundle\EventDispatcher\AMQP\AmqpBeforeProcess;
use Anboo\ApiBundle\Service\CRUD\UpdateItemService;
use Anboo\ApiBundle\Service\DTO\DTOFactory;
use Webslon\Library\Api\Exception\ApiException;

class UpdateConsumer extends AbstractCrudConsumer
{
    /**
     * @var string
     */
    protected $topicName = Route::TOPIC_UPDATE_PREFIX;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UpdateItemService
     */
    private $updateItemService;

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
     * @param UpdateItemService $updateItemService
     */
    public function setUpdateItemService(UpdateItemService $updateItemService)
    {
        $this->updateItemService = $updateItemService;
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
        $this->updateItemService->setRequest($request);

        $event = new AmqpBeforeProcess($packet, __CLASS__);
        $this->updateItemService->getDependencies()->getDispatcher()->dispatch(AmqpBeforeProcess::AMQP_EVENT_BEFORE_PROCESS, $event);

        $entityId = $packet->getField('id');
        $updateData = $packet->getData();

        try {
            $this->entityManager->getMetadataFactory()->getMetadataFor($this->entityClass);
            $isEntity = true;
        } catch (MappingException $mappingException) {
            $isEntity = false;
        }

        if (!$isEntity) {
            $this->updateItemService->setDtoClass($this->entityClass);

            $entityClassForDto = $this->dtoFactory->getEntityClassForDto($this->entityClass);
            if (!$entityClassForDto) {
                throw new AmqpFatalException('DTO class '.$this->entityClass.' must use DTO annotation');
            }

            $entityClass = $entityClassForDto;
        } else {
            $entityClass = $this->entityClass;
        }

        $entity = $this->entityManager->find($entityClass, $entityId);
        if (!$entity) {
            throw (new AmqpNotFoundEntityException('Entity ('.$entityClass.') by id='.$entityId.' not found', 'Error_404', null, Response::HTTP_NOT_FOUND))
                ->setEntityId($entityId);
        }

        $response = $this->updateItemService->update($entityId, json_encode($updateData), $entityClass);

        if (array_filter($this->getReplyData($packet))) {
            $this->reply(json_decode($response->toJsonResponse(), true));
        }

        $this->entityManager->clear();
        gc_collect_cycles();
    }
}