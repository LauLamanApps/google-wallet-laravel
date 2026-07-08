<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Fixtures;

use LauLamanApps\GoogleWallet\Api\HttpResponse;
use LauLamanApps\GoogleWallet\Callback\CallbackVerifier;
use LauLamanApps\GoogleWallet\Callback\GoogleKeyProvider;

/**
 * Builds real, correctly signed Google Wallet callback request bodies (the
 * 'ECv2SigningOnly' scheme) and a CallbackVerifier that trusts the fixture's
 * root signing key. CallbackVerifier is final and cannot be mocked, so the
 * tests bind this real verifier into the container instead.
 */
final class CallbackFixture
{
    public const ISSUER_ID = '3388000000012345678';

    private const SENDER_ID = 'GooglePayPasses';
    private const PROTOCOL_VERSION = 'ECv2SigningOnly';

    private function __construct(
        private readonly EcKeyPairFixture $rootKey,
        private readonly EcKeyPairFixture $intermediateKey,
    ) {
    }

    public static function create(): self
    {
        return new self(EcKeyPairFixture::generate(), EcKeyPairFixture::generate());
    }

    /**
     * A real CallbackVerifier whose GoogleKeyProvider serves this fixture's
     * root signing key from a stubbed HttpClient (no network access).
     */
    public function verifier(): CallbackVerifier
    {
        $httpClient = new FakeHttpClient(new HttpResponse(200, $this->keysResponse()));

        return new CallbackVerifier(self::ISSUER_ID, GoogleKeyProvider::production($httpClient));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function signedBody(array $overrides = []): string
    {
        $message = $overrides + [
            'classId' => self::ISSUER_ID . '.membership',
            'objectId' => self::ISSUER_ID . '.member-0001',
            'eventType' => 'save',
            'expTimeMillis' => (string) ((time() + 3600) * 1000),
            'count' => 1,
            'nonce' => 'nonce-123',
        ];

        $signedKey = json_encode([
            'keyValue' => $this->intermediateKey->publicKeyBase64Der,
            'keyExpiration' => (string) ((time() + 3600) * 1000),
        ], JSON_THROW_ON_ERROR);

        $keySignature = $this->rootKey->sign(
            self::lengthValue(self::SENDER_ID)
            . self::lengthValue(self::PROTOCOL_VERSION)
            . self::lengthValue($signedKey)
        );

        $signedMessage = json_encode($message, JSON_THROW_ON_ERROR);

        $messageSignature = $this->intermediateKey->sign(
            self::lengthValue(self::SENDER_ID)
            . self::lengthValue(self::ISSUER_ID)
            . self::lengthValue(self::PROTOCOL_VERSION)
            . self::lengthValue($signedMessage)
        );

        return json_encode([
            'protocolVersion' => self::PROTOCOL_VERSION,
            'intermediateSigningKey' => [
                'signedKey' => $signedKey,
                'signatures' => [$keySignature],
            ],
            'signature' => $messageSignature,
            'signedMessage' => $signedMessage,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * A body whose signed message was modified after signing: the signature
     * no longer matches and verification must fail.
     */
    public function tamperedBody(): string
    {
        $body = json_decode($this->signedBody(), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($body) && is_string($body['signedMessage']));

        $body['signedMessage'] = str_replace('member-0001', 'member-0002', $body['signedMessage']);

        return json_encode($body, JSON_THROW_ON_ERROR);
    }

    private function keysResponse(): string
    {
        return json_encode([
            'keys' => [
                [
                    'keyValue' => $this->rootKey->publicKeyBase64Der,
                    'protocolVersion' => self::PROTOCOL_VERSION,
                    'keyExpiration' => (string) ((time() + 3600) * 1000),
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private static function lengthValue(string $value): string
    {
        return pack('V', strlen($value)) . $value;
    }
}
