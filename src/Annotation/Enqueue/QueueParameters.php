<?php

namespace Anboo\ApiBundle\Annotation\Enqueue;

/**
 * Class QueueParameters
 * @Annotation
 */
class QueueParameters extends Parameters
{
    /** @var bool */
    public $exclusive = false;
}