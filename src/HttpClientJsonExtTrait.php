<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function array_merge;
use function http_build_query;

trait HttpClientJsonExtTrait
{
    use HttpClientExtTrait;

    /**
     * @param string $method
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @param array $headers
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function requestJson(string $method, string $uri, $parameters = null, $headers = [])
    {
        $content = null;
        if (is_string($parameters) && !empty($parameters)) {
            $content = $parameters;
        }
        if (is_array($parameters) && !empty($parameters)) {
            $content = $this->getJsonHelper()->jsonEncode($parameters);
        }

        $headers = array_merge(
            [
                self::$headerContentType => 'application/json',
                // заголовок сообщающий: ожидается ответ в формате json
                // фреймворк по нему определяет в каком формате возвращать ответ (например ответ с ошибками)
                self::$headerAccept => 'application/json',
            ],
            $headers
        );

        return $this->sendRequest($method, $uri, [], [], $headers, $content);
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function get(string $url, array $parameters = [])
    {
        return $this->requestJson('GET', $url . ($parameters ? '?' . http_build_query($parameters) : ''));
    }

    /**
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function post(string $uri, $parameters)
    {
        return $this->requestJson('POST', $uri, $parameters);
    }

    /**
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function put(string $uri, $parameters)
    {
        return $this->requestJson('PUT', $uri, $parameters);
    }

    /**
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function patch(string $uri, $parameters)
    {
        return $this->requestJson('PATCH', $uri, $parameters);
    }

    /**
     * @param string $uri
     * @param array|string $parameters array OR json string
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function patchMerge(string $uri, $parameters)
    {
        $headers = [self::$headerContentType => 'application/merge-patch+json'];

        return $this->requestJson('PATCH', $uri, $parameters, $headers);
    }

    /**
     * @param string $uri
     * @return Crawler|ResponseInterface|null
     * @throws TransportExceptionInterface
     */
    protected function delete(string $uri)
    {
        return $this->requestJson('DELETE', $uri);
    }
}
