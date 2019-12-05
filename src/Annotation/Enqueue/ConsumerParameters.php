<?php

namespace Anboo\RabbitmqBundle\Annotation\Enqueue;

/**
 * Class ConsumerParameters
 * @Annotation
 */
class ConsumerParameters
{
    /** @var bool */
    public $noLocal = false;

    /** @var bool */
    public $noAck = false;

    /** @var bool */
    public $exclusive = false;

    /** @var bool */
    public $noWait = false;

    /** @var array */
    public $arguments = [];
}