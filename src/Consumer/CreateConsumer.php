<?php


namespace Anboo\ApiBundle\Consumer;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Anboo\ApiBundle\AMQP\Packet;
use Anboo\ApiBundle\AMQP\Router\Route;
use Anboo\ApiBundle\EventDispatcher\AMQP\AmqpBeforeProcess;
use Anboo\ApiBundle\Model\JsonDataEntityInterface;
use Anboo\ApiBundle\Service\CRUD\AddItemService;
use Webslon\Library\Serializer\Service\SerializeService;

/**
 * Class CreateConsumer
 */
class CreateConsumer extends AbstractCrudConsumer
{
    /**
     * @var string
     */
    protected $topicName = Route::TOPIC_CREATE_PREFIX;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AddItemService
     */
    private $addItemManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @required
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager (EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @required
     * @param AddItemService $addItemManager
     */
    public function setAddItemManager (AddItemService $addItemManager)
    {
        $this->addItemManager = $addItemManager;
    }

    /**
     * @required
     * @param SerializeService $serializer
     */
    public function setSerializer(SerializeService $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param Packet $packet
     * @return boolean
     * @throws \Exception
     */
    public function doProcess(Packet $packet)
    {
        $request = new Request();
        $request->setMethod('POST');
        $this->addItemManager->setRequest($request);

        $event = new AmqpBeforeProcess($packet, __CLASS__);
        $this->addItemManager->getDependencies()->getDispatcher()->dispatch(AmqpBeforeProcess::AMQP_EVENT_BEFORE_PROCESS, $event);

        $creationData = $packet->getData();

        try {
            $this->entityManager->getMetadataFactory()->getMetadataFor($this->entityClass);
            $isEntity = true;
        } catch (MappingException $mappingException) {
            $isEntity = false;
        }

        if (!$isEntity) {
            $this->addItemManager->setDtoClass($this->entityClass);
        }

        $response = $this->addItemManager->add(
            json_encode($creationData),
            $this->entityClass
        );

        if (array_filter($this->getReplyData($packet))) {
            $this->reply(json_decode($response->toJsonResponse(), true));
        }
        $this->entityManager->clear();
        gc_collect_cycles();

        return true;
    }
}
