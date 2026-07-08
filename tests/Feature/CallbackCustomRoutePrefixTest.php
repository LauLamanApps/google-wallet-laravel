<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWalletLaravel\Tests\Fixtures\CallbackFixture;
use LauLamanApps\GoogleWalletLaravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CallbackCustomRoutePrefixTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app->make('config');

        $config->set('google-wallet.callback.enabled', true);
        $config->set('google-wallet.callback.issuer_id', CallbackFixture::ISSUER_ID);
        $config->set('google-wallet.callback.route_prefix', '/wallet/hooks');
    }

    #[Test]
    public function itRegistersTheCallbackRouteUnderTheConfiguredPrefix(): void
    {
        self::assertSame('wallet/hooks/callback', Route::getRoutes()->getByName('google-wallet.callback')?->uri());
    }

    #[Test]
    public function theCallbackEndpointRespondsUnderTheConfiguredPrefix(): void
    {
        $fixture = CallbackFixture::create();
        $this->laravel()->instance(CallbackVerifier::class, $fixture->verifier());

        $this->call('POST', '/wallet/hooks/callback', [], [], [], ['CONTENT_TYPE' => 'application/json'], $fixture->signedBody())
            ->assertStatus(200);

        $this->call('POST', '/google-wallet/callback', [], [], [], ['CONTENT_TYPE' => 'application/json'], $fixture->signedBody())
            ->assertStatus(404);
    }
}
