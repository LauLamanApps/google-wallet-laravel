<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Fixtures;

use RuntimeException;

final class ServiceAccountFixture
{
    public const CLIENT_EMAIL = 'google-wallet-laravel-test@example.iam.gserviceaccount.com';

    /** @var string[] */
    private static array $files = [];

    /**
     * Generates a service account JSON key file on the fly so tests never
     * need (or commit) real Google Cloud key material.
     */
    public static function createJsonKeyFile(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new RuntimeException('Unable to generate a private key: ' . (string) openssl_error_string());
        }

        if (!openssl_pkey_export($key, $privateKey)) {
            throw new RuntimeException('Unable to export the private key: ' . (string) openssl_error_string());
        }

        $path = tempnam(sys_get_temp_dir(), 'google-wallet-laravel-test-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary file for the service account key.');
        }

        file_put_contents($path, json_encode([
            'client_email' => self::CLIENT_EMAIL,
            'private_key' => $privateKey,
        ], JSON_THROW_ON_ERROR));

        self::$files[] = $path;

        return $path;
    }

    public static function cleanUp(): void
    {
        foreach (self::$files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        self::$files = [];
    }
}
