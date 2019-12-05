<?php


namespace Anboo\ApiBundle\Consumer;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Anboo\ApiBundle\AMQP\Exception\AmqpFatalException;
use Anboo\ApiBundle\AMQP\Exception\AmqpNotFoundEntityException;
use Anboo\ApiBundle\AMQP\Packet;
use Anboo\ApiBundle\AMQP\Router\Route;
use Anboo\ApiBundle\EventDispatcher\AMQP\AmqpBeforeProcess;
use Anboo\ApiBundle\Service\CRUD\DeleteItemService;
use Anboo\ApiBundle\Service\DTO\DTOFactory;

/**
 * Class DeleteConsumer
 */
class DeleteConsumer extends AbstractCrudConsumer
{
    /**
     * @var string
     */
    protected $topicName = Route::TOPIC_DELETE_PREFIX;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var DeleteItemService
     */
    protected $deleteItemService;

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
     * @param DeleteItemService $deleteItemService
     */
    public function setDeleteItemService(DeleteItemService $deleteItemService)
    {
        $this->deleteItemService = $deleteItemService;
    }

    /**
     * @param Packet $packet
     *
     * @throws \ReflectionException
     */
    public function doProcess(Packet $packet)
    {
        $request = new Request();
        $request->setMethod('DELETE');
        $this->deleteItemService->setRequest($request);

        $event = new AmqpBeforeProcess($packet, __CLASS__);
        $this->deleteItemService->getDependencies()->getDispatcher()->dispatch(AmqpBeforeProcess::AMQP_EVENT_BEFORE_PROCESS, $event);

        try {
            $this->entityManager->getMetadataFactory()->getMetadataFor($this->entityClass);
            $isEntity = true;
        } catch (MappingException $mappingException) {
            $isEntity = false;
        }

        if (!$isEntity) {
            $this->deleteItemService->setDtoClass($this->entityClass);

            $entityClassForDto = $this->dtoFactory->getEntityClassForDto($this->entityClass);
            if (!$entityClassForDto) {
                throw new AmqpFatalException('DTO class '.$this->entityClass.' must use DTO annotation');
            }

            $entityClass = $entityClassForDto;
        } else {
            $entityClass = $this->entityClass;
        }

        $entityId = $packet->getField('id');
        $entity = $this->entityManager->find($entityClass, $entityId);
        if (!$entity) {
            throw (new AmqpNotFoundEntityException('Entity ('.$entityClass.') by id='.$entityId.' not found', 'Error_404', null, Response::HTTP_NOT_FOUND))
                ->setEntityId($entityId);
        }

        $this->deleteItemService->deleteEntity($entity);

        $this->entityManager->clear();
        gc_collect_cycles();
    }
}
