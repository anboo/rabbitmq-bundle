<?php

namespace Anboo\RabbitmqBundle\Annotation\Enqueue;

/**
 * Class QueueParameters
 * @Annotation
 */
class QueueParameters extends Parameters
{
    /** @var bool */
    public $exclusive = false;
    
    /** @var bool */
    public $consume = true;
}