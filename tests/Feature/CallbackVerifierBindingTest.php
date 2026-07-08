<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWallet\Callback\GoogleKeyProvider;
use LauLamanApps\GoogleWalletLaravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use RuntimeException;

final class CallbackVerifierBindingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app->make('config');

        $config->set('google-wallet.callback.enabled', true);
    }

    #[Test]
    public function itThrowsAnExceptionWhenNoIssuerIdIsConfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No Google Wallet issuer id configured.');

        $this->laravel()->make(CallbackVerifier::class);
    }

    #[Test]
    public function itRegistersTheCallbackVerifierAsASingleton(): void
    {
        config()->set('google-wallet.callback.issuer_id', '3388000000012345678');

        $verifier = $this->laravel()->make(CallbackVerifier::class);

        self::assertInstanceOf(CallbackVerifier::class, $verifier);
        self::assertSame($verifier, $this->laravel()->make(CallbackVerifier::class));
        self::assertTrue($this->laravel()->isShared(CallbackVerifier::class));
    }

    #[Test]
    public function itAcceptsAnIntegerIssuerId(): void
    {
        config()->set('google-wallet.callback.issuer_id', 3388000000012345678);

        self::assertInstanceOf(CallbackVerifier::class, $this->laravel()->make(CallbackVerifier::class));
    }

    #[Test]
    public function itUsesTheProductionGoogleKeysByDefault(): void
    {
        $keyProvider = $this->laravel()->make(GoogleKeyProvider::class);

        self::assertInstanceOf(GoogleKeyProvider::class, $keyProvider);
        self::assertSame($keyProvider, $this->laravel()->make(GoogleKeyProvider::class));
        self::assertTrue($this->laravel()->isShared(GoogleKeyProvider::class));
        self::assertSame(GoogleKeyProvider::PRODUCTION_KEYS_URL, self::keysUrl($keyProvider));
    }

    #[Test]
    public function itUsesTheTestGoogleKeysWhenTheEnvironmentIsTest(): void
    {
        config()->set('google-wallet.callback.environment', 'test');

        $keyProvider = $this->laravel()->make(GoogleKeyProvider::class);

        self::assertInstanceOf(GoogleKeyProvider::class, $keyProvider);
        self::assertSame(GoogleKeyProvider::TEST_KEYS_URL, self::keysUrl($keyProvider));
    }

    #[Test]
    public function itThrowsAnExceptionForAnUnknownEnvironment(): void
    {
        config()->set('google-wallet.callback.environment', 'staging');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Google Wallet callback environment.');

        $this->laravel()->make(GoogleKeyProvider::class);
    }

    private static function keysUrl(GoogleKeyProvider $keyProvider): string
    {
        $keysUrl = (new ReflectionProperty(GoogleKeyProvider::class, 'keysUrl'))->getValue($keyProvider);
        assert(is_string($keysUrl));

        return $keysUrl;
    }
}
