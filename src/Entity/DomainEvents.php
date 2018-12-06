<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 06.12.18
 * Time: 7:47
 */

namespace Anboo\ApiBundle\Entity;

/**
 * Trait DomainEvents
 */
trait DomainEvents
{
    private $events = [];

    /**
     * @param DomainEventInterface $event
     */
    public function registerEvent(DomainEventInterface $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return DomainEventInterface[]
     */
    public function releaseEvents()
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}