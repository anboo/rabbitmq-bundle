<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 06.12.18
 * Time: 7:48
 */

namespace Anboo\ApiBundle\Entity;

interface DomainEventInterface
{
    /** @return string */
    public function getEventCode();
}