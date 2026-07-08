<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWallet\Callback\GoogleKeyProvider;
use LauLamanApps\GoogleWalletLaravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CallbackDisabledTest extends TestCase
{
    #[Test]
    public function itDoesNotRegisterTheCallbackRouteWhenDisabled(): void
    {
        self::assertNull(Route::getRoutes()->getByName('google-wallet.callback'));
    }

    #[Test]
    public function theCallbackEndpointDoesNotRespondWhenDisabled(): void
    {
        $this->call('POST', '/google-wallet/callback', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')
            ->assertStatus(404);
    }

    #[Test]
    public function itDoesNotEagerlyResolveTheCallbackVerifier(): void
    {
        self::assertFalse($this->laravel()->resolved(CallbackVerifier::class));
        self::assertFalse($this->laravel()->resolved(GoogleKeyProvider::class));
    }
}
