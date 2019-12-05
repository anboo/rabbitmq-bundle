<?php

namespace Anboo\ApiBundle\Annotation\Enqueue;

/**
 * Class Produce
 * @Annotation
 */
class Produce
{
    /** @var string */
    public $exchange;

    /** @var string */
    public $routingKey;

    /** @var string */
    public $queue;
}