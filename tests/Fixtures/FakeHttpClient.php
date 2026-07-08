<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Tests\Fixtures;

use LauLamanApps\GoogleWallet\Api\HttpClient;
use LauLamanApps\GoogleWallet\Api\HttpResponse;

final class FakeHttpClient implements HttpClient
{
    /** @var list<array{method: string, url: string}> */
    public array $requests = [];

    public function __construct(
        private readonly HttpResponse $response,
    ) {
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): HttpResponse
    {
        $this->requests[] = ['method' => $method, 'url' => $url];

        return $this->response;
    }
}
