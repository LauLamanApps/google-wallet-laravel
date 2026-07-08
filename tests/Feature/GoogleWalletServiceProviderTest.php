<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use LauLamanApps\GoogleWallet\JwtSigner;
use LauLamanApps\GoogleWallet\PassPayload;
use LauLamanApps\GoogleWallet\SaveUrlFactory;
use LauLamanApps\GoogleWallet\ServiceAccount;
use LauLamanApps\GoogleWalletLaravel\GoogleWalletServiceProvider;
use LauLamanApps\GoogleWalletLaravel\Tests\Fixtures\ServiceAccountFixture;
use LauLamanApps\GoogleWalletLaravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class GoogleWalletServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        ServiceAccountFixture::cleanUp();

        parent::tearDown();
    }

    #[Test]
    public function itMergesTheDefaultConfiguration(): void
    {
        self::assertNull(config('google-wallet.service_account'));
        self::assertSame([], config('google-wallet.origins'));
    }

    #[Test]
    public function itRegistersTheServiceAccountAsASingleton(): void
    {
        config()->set('google-wallet.service_account', ServiceAccountFixture::createJsonKeyFile());

        $serviceAccount = $this->laravel()->make(ServiceAccount::class);

        self::assertInstanceOf(ServiceAccount::class, $serviceAccount);
        self::assertSame(ServiceAccountFixture::CLIENT_EMAIL, $serviceAccount->getClientEmail());
        self::assertSame($serviceAccount, $this->laravel()->make(ServiceAccount::class));
        self::assertTrue($this->laravel()->isShared(ServiceAccount::class));
    }

    #[Test]
    public function itRegistersTheJwtSignerAsASingleton(): void
    {
        config()->set('google-wallet.service_account', ServiceAccountFixture::createJsonKeyFile());

        $signer = $this->laravel()->make(JwtSigner::class);

        self::assertInstanceOf(JwtSigner::class, $signer);
        self::assertSame($signer, $this->laravel()->make(JwtSigner::class));
        self::assertTrue($this->laravel()->isShared(JwtSigner::class));
    }

    #[Test]
    public function itRegistersTheSaveUrlFactoryAsASingleton(): void
    {
        config()->set('google-wallet.service_account', ServiceAccountFixture::createJsonKeyFile());

        $factory = $this->laravel()->make(SaveUrlFactory::class);

        self::assertInstanceOf(SaveUrlFactory::class, $factory);
        self::assertSame($factory, $this->laravel()->make(SaveUrlFactory::class));
        self::assertSame($factory, $this->laravel()->make('google-wallet.save-url-factory'));
        self::assertTrue($this->laravel()->isShared(SaveUrlFactory::class));
    }

    #[Test]
    public function itPassesTheConfiguredOriginsToTheSaveUrlFactory(): void
    {
        config()->set('google-wallet.service_account', ServiceAccountFixture::createJsonKeyFile());
        config()->set('google-wallet.origins', ['https://example.com', 'https://www.example.com']);

        $factory = $this->laravel()->make(SaveUrlFactory::class);
        self::assertInstanceOf(SaveUrlFactory::class, $factory);

        $claims = $this->decodeJwtClaims($factory->create(new PassPayload()));

        self::assertSame(ServiceAccountFixture::CLIENT_EMAIL, $claims['iss'] ?? null);
        self::assertSame('savetowallet', $claims['typ'] ?? null);
        self::assertSame(['https://example.com', 'https://www.example.com'], $claims['origins'] ?? null);
    }

    #[Test]
    public function itThrowsAnExceptionWhenNoServiceAccountIsConfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No Google Wallet service account configured.');

        $this->laravel()->make(ServiceAccount::class);
    }

    #[Test]
    public function itPublishesTheConfigFile(): void
    {
        $paths = ServiceProvider::pathsToPublish(GoogleWalletServiceProvider::class, 'google-wallet-config');
        $source = realpath(__DIR__ . '/../../config/google-wallet.php');

        self::assertNotFalse($source);
        self::assertCount(1, $paths);
        self::assertSame($source, realpath((string) array_key_first($paths)));
        self::assertSame($this->laravel()->configPath('google-wallet.php'), $paths[array_key_first($paths)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtClaims(string $saveUrl): array
    {
        self::assertStringStartsWith('https://pay.google.com/gp/v/save/', $saveUrl);

        $jwt = substr($saveUrl, strlen('https://pay.google.com/gp/v/save/'));
        $parts = explode('.', $jwt);
        self::assertCount(3, $parts);

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        self::assertIsString($payload);

        $claims = json_decode($payload, true);
        self::assertIsArray($claims);

        /** @var array<string, mixed> $claims */
        return $claims;
    }
}
