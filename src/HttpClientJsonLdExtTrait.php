<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use function http_build_query;

trait HttpClientJsonLdExtTrait
{
    use HttpClientJsonExtTrait;

    protected function requestJsonLd(string $method, string $uri, array $parameters = [])
    {
        return $this->requestJson($method, $uri, $parameters, [
            self::$headerAccept => 'application/ld+json',
            self::$headerContentType => 'application/ld+json',
        ]);
    }

    protected function getLd(string $url, array $parameters = [])
    {
        return $this->requestJsonLd('GET', $url . ($parameters ? '?' . http_build_query($parameters) : ''));
    }

    protected function postLd(string $uri, array $parameters = [])
    {
        return $this->requestJsonLd('POST', $uri, $parameters);
    }

    protected function putLd(string $uri, array $parameters = [])
    {
        return $this->requestJsonLd('PUT', $uri, $parameters);
    }

    protected function deleteLd(string $uri, array $parameters = [])
    {
        return $this->requestJsonLd('DELETE', $uri, $parameters);
    }
}
