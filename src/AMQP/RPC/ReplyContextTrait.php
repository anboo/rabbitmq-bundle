<?php

namespace Anboo\ApiBundle\AMQP\RPC;

/**
 * Class ReplyContextTrait
 */
trait ReplyContextTrait
{
    /** @var ReplyContext */
    private $replyContext;

    /**
     * @return ReplyContext
     */
    public function replyContext(): ReplyContext
    {
        if (!$this->replyContext instanceof ReplyContext) {
            $this->replyContext = new ReplyContext();
        }

        return $this->replyContext;
    }

    /**
     * @return $this
     */
    public function clearReplyContext() : self
    {
        if ($this->replyContext) {
            $this->replyContext->clear();
        }

        return $this;
    }
}