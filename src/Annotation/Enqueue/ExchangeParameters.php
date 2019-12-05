<?php

namespace Anboo\ApiBundle\Annotation\Enqueue;

/**
 * Class ExchangeParameters
 * @Annotation
 */
class ExchangeParameters extends Parameters
{
    /** @var bool */
    public $internal = false;

    /** @var string */
    public $type = 'topic';
}