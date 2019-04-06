<?php

namespace Anboo\ApiBundle\EventSubscriber;

use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    public function onRequest(GetResponseEvent $getResponseEvent)
    {
        if (!$requestId = $getResponseEvent->getRequest()->headers->get('X-Request-Id')) {
            $getResponseEvent->getRequest()->headers->set('X-Request-Id', Uuid::uuid4()->toString());
        } else {
            if (!Uuid::isValid($requestId)) {
                $getResponseEvent->getRequest()->headers->set('X-Request-Id', Uuid::uuid4()->toString());
            }
        }
    }
}