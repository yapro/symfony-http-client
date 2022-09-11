<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function http_build_query;

trait HttpClientJsonLdExtTrait
{
    use HttpClientJsonExtTrait;

    /**
     * @param string $method
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function requestJsonLd(string $method, string $uri, $parameters = null)
    {
        return $this->requestJson($method, $uri, $parameters, [
            self::$headerAccept => 'application/ld+json',
            self::$headerContentType => 'application/ld+json',
        ]);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function getLd(string $url, array $parameters = [])
    {
        return $this->requestJsonLd('GET', $url . ($parameters ? '?' . http_build_query($parameters) : ''));
    }

    /**
     * @param string $uri
     * @param array|string|null $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function postLd(string $uri, $parameters = null)
    {
        return $this->requestJsonLd('POST', $uri, $parameters);
    }

    /**
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function putLd(string $uri, $parameters)
    {
        return $this->requestJsonLd('PUT', $uri, $parameters);
    }

    /**
     * @param string $uri
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function deleteLd(string $uri)
    {
        return $this->requestJsonLd('DELETE', $uri);
    }
}
