<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Events;

use LauLamanApps\GoogleWallet\Callback\CallbackEvent;

abstract class AbstractPassEvent
{
    public function __construct(
        private readonly CallbackEvent $callbackEvent,
    ) {
    }

    /**
     * The fully qualified object id ('<issuerId>.<objectIdentifier>').
     */
    final public function getObjectId(): string
    {
        return $this->callbackEvent->getObjectId();
    }

    /**
     * The fully qualified class id ('<issuerId>.<classIdentifier>').
     */
    final public function getClassId(): string
    {
        return $this->callbackEvent->getClassId();
    }

    final public function getCallbackEvent(): CallbackEvent
    {
        return $this->callbackEvent;
    }
}
