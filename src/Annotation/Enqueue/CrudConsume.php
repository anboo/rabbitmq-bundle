<?php

namespace Anboo\ApiBundle\Annotation\Enqueue;

/**
 * @Annotation
 * @Target("CLASS")
 */
class CrudConsume
{
    /**
     * CrudConsume constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->topicsMap = $values['value'];
    }

    /**
     * @var Consume[]
     */
    public $topicsMap;
}