<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWallet\Callback\EventType;
use LauLamanApps\GoogleWalletLaravel\Events\PassDeletedEvent;
use LauLamanApps\GoogleWalletLaravel\Events\PassSavedEvent;
use LauLamanApps\GoogleWalletLaravel\Tests\Fixtures\CallbackFixture;
use LauLamanApps\GoogleWalletLaravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CallbackControllerTest extends TestCase
{
    private CallbackFixture $fixture;

    protected function defineEnvironment($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app->make('config');

        $config->set('google-wallet.callback.enabled', true);
        $config->set('google-wallet.callback.issuer_id', CallbackFixture::ISSUER_ID);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = CallbackFixture::create();
        $this->laravel()->instance(CallbackVerifier::class, $this->fixture->verifier());
    }

    #[Test]
    public function itDispatchesAPassSavedEventForAVerifiedSaveCallback(): void
    {
        Event::fake();

        $response = $this->postCallback($this->fixture->signedBody());

        $response->assertStatus(200);
        self::assertSame('{}', $response->getContent());

        Event::assertDispatched(PassSavedEvent::class, static function (PassSavedEvent $event): bool {
            return $event->getObjectId() === CallbackFixture::ISSUER_ID . '.member-0001'
                && $event->getClassId() === CallbackFixture::ISSUER_ID . '.membership'
                && $event->getCallbackEvent()->getEventType() === EventType::Save
                && $event->getCallbackEvent()->getNonce() === 'nonce-123';
        });
        Event::assertNotDispatched(PassDeletedEvent::class);
    }

    #[Test]
    public function itDispatchesAPassDeletedEventForAVerifiedDeleteCallback(): void
    {
        Event::fake();

        $response = $this->postCallback($this->fixture->signedBody(['eventType' => 'del']));

        $response->assertStatus(200);
        self::assertSame('{}', $response->getContent());

        Event::assertDispatched(PassDeletedEvent::class, static function (PassDeletedEvent $event): bool {
            return $event->getObjectId() === CallbackFixture::ISSUER_ID . '.member-0001'
                && $event->getClassId() === CallbackFixture::ISSUER_ID . '.membership'
                && $event->getCallbackEvent()->getEventType() === EventType::Delete;
        });
        Event::assertNotDispatched(PassSavedEvent::class);
    }

    #[Test]
    public function itRejectsACallbackWithAnInvalidSignature(): void
    {
        Event::fake();

        $response = $this->postCallback($this->fixture->tamperedBody());

        $response->assertStatus(400);
        self::assertSame('{}', $response->getContent());

        Event::assertNotDispatched(PassSavedEvent::class);
        Event::assertNotDispatched(PassDeletedEvent::class);
    }

    #[Test]
    public function itRejectsAGarbageRequestBody(): void
    {
        Event::fake();

        $response = $this->postCallback('not json');

        $response->assertStatus(400);
        self::assertSame('{}', $response->getContent());

        Event::assertNotDispatched(PassSavedEvent::class);
        Event::assertNotDispatched(PassDeletedEvent::class);
    }

    /**
     * @return TestResponse<\Illuminate\Http\JsonResponse>
     */
    private function postCallback(string $body): TestResponse
    {
        return $this->call('POST', '/google-wallet/callback', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
    }
}
