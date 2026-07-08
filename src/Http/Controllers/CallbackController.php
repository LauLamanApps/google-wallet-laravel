<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Http\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWallet\Callback\EventType;
use LauLamanApps\GoogleWallet\Exception\CallbackException;
use LauLamanApps\GoogleWalletLaravel\Events\PassDeletedEvent;
use LauLamanApps\GoogleWalletLaravel\Events\PassSavedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class CallbackController
{
    public function __construct(
        private readonly CallbackVerifier $verifier,
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function callback(Request $request): JsonResponse
    {
        try {
            $event = $this->verifier->verify($request->getContent());
        } catch (CallbackException $exception) {
            $this->logger->warning('Google Wallet callback rejected: ' . $exception->getMessage());

            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $this->events->dispatch(match ($event->getEventType()) {
            EventType::Save => new PassSavedEvent($event),
            EventType::Delete => new PassDeletedEvent($event),
        });

        return new JsonResponse();
    }
}
