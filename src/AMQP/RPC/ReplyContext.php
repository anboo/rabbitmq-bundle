<?php

namespace Anboo\ApiBundle\AMQP\RPC;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Anboo\ApiBundle\Util\PropertyAccessor;

class ReplyContext
{
    private $replyContext = [];

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->replyContext;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $keys = explode('.', $key);
        $value = $this->replyContext;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value) : self
    {
        $key = '['.str_replace('.', '][', $key).']';

        $pa = PropertyAccess::createPropertyAccessor();
        $pa->setValue($this->replyContext, $key, $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function clear() : self
    {
        $this->replyContext = [];

        return $this;
    }

    /**
     * @param array $replyContext
     * @return $this
     */
    public function load(array $replyContext) : self
    {
        $this->replyContext = $replyContext ?? [];

        return $this;
    }
}