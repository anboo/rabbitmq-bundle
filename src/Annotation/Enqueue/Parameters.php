<?php

namespace Anboo\ApiBundle\Annotation\Enqueue;

/**
 * Class Parameters
 * @Annotation
 */
class Parameters
{
    /**
     * @var bool
     */
    public $passive = false;

    /**
     * @var bool
     */
    public $durable = true;

    /**
     * @var bool
     */
    public $autoDelete = false;

    /**
     * @var array
     */
    public $arguments = [];
}