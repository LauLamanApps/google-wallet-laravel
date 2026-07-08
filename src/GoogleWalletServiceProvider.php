<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use LauLamanApps\GoogleWallet\JwtSigner;
use LauLamanApps\GoogleWallet\SaveUrlFactory;
use LauLamanApps\GoogleWallet\ServiceAccount;
use RuntimeException;

final class GoogleWalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-wallet.php', 'google-wallet');

        $this->app->singleton(ServiceAccount::class, static function (Container $app): ServiceAccount {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            $serviceAccount = $config->get('google-wallet.service_account');
            if (!is_string($serviceAccount) || $serviceAccount === '') {
                throw new RuntimeException(
                    'No Google Wallet service account configured. Set the "google-wallet.service_account" config key ' .
                    '(GOOGLE_WALLET_SERVICE_ACCOUNT) to the path of your service account JSON key file.'
                );
            }

            return ServiceAccount::fromJsonFile($serviceAccount);
        });

        $this->app->singleton(JwtSigner::class, static function (Container $app): JwtSigner {
            return new JwtSigner($app->make(ServiceAccount::class));
        });

        $this->app->singleton(SaveUrlFactory::class, static function (Container $app): SaveUrlFactory {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $app->make('config');

            $origins = $config->get('google-wallet.origins', []);

            /** @var string[] $origins */
            $origins = is_array($origins) ? array_values($origins) : [];

            return new SaveUrlFactory($app->make(ServiceAccount::class), $origins);
        });

        $this->app->alias(SaveUrlFactory::class, 'google-wallet.save-url-factory');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/google-wallet.php' => $this->app->configPath('google-wallet.php'),
        ], 'google-wallet-config');
    }
}
