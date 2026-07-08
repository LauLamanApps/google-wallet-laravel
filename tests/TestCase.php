<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests;

use Illuminate\Foundation\Application;
use LauLamanApps\GoogleWalletLaravel\GoogleWalletServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GoogleWalletServiceProvider::class,
        ];
    }

    protected function laravel(): Application
    {
        assert($this->app instanceof Application);

        return $this->app;
    }
}
