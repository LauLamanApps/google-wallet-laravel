<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use LauLamanApps\GoogleWallet\Callback\CallbackEvent;
use LauLamanApps\GoogleWallet\Callback\EventType;
use LauLamanApps\GoogleWalletLaravel\Events\PassDeletedEvent;
use LauLamanApps\GoogleWalletLaravel\Events\PassSavedEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PassEventsTest extends TestCase
{
    #[Test]
    public function thePassSavedEventExposesTheCallbackEvent(): void
    {
        $callbackEvent = new CallbackEvent(
            '3388000000012345678.membership',
            '3388000000012345678.member-0001',
            EventType::Save,
            (time() + 3600) * 1000,
            'nonce-123',
            1,
        );

        $event = new PassSavedEvent($callbackEvent);

        self::assertSame('3388000000012345678.member-0001', $event->getObjectId());
        self::assertSame('3388000000012345678.membership', $event->getClassId());
        self::assertSame($callbackEvent, $event->getCallbackEvent());
    }

    #[Test]
    public function thePassDeletedEventExposesTheCallbackEvent(): void
    {
        $callbackEvent = new CallbackEvent(
            '3388000000012345678.membership',
            '3388000000012345678.member-0001',
            EventType::Delete,
            (time() + 3600) * 1000,
        );

        $event = new PassDeletedEvent($callbackEvent);

        self::assertSame('3388000000012345678.member-0001', $event->getObjectId());
        self::assertSame('3388000000012345678.membership', $event->getClassId());
        self::assertSame($callbackEvent, $event->getCallbackEvent());
    }
}
