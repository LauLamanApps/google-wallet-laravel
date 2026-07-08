<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Fixtures;

use RuntimeException;

final class EcKeyPairFixture
{
    private function __construct(
        public readonly string $privateKey,
        public readonly string $publicKeyBase64Der,
    ) {
    }

    public static function generate(): self
    {
        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($key === false) {
            throw new RuntimeException('Failed to generate an EC key pair.');
        }

        $privateKey = '';
        if (!openssl_pkey_export($key, $privateKey)) {
            throw new RuntimeException('Failed to export the EC private key.');
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || !isset($details['key']) || !is_string($details['key'])) {
            throw new RuntimeException('Failed to extract the EC public key.');
        }

        $base64Der = str_replace(
            ['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"],
            '',
            $details['key']
        );

        return new self($privateKey, $base64Der);
    }

    public function sign(string $data): string
    {
        if (!openssl_sign($data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to create an ECDSA signature.');
        }

        return base64_encode($signature);
    }
}
