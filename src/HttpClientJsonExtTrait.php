<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use function array_merge;
use function http_build_query;

trait HttpClientJsonExtTrait
{
    use HttpClientExtTrait;

    protected function requestJson(string $method, string $uri, array $parameters = [], $headers = [])
    {
        $content = null;
        if ($parameters) {
            $content = $this->getJsonHelper()->jsonEncode($parameters);
        }

        $headers = array_merge(
            [
                self::$headerContentType => 'application/json',
                // заголовок сообщающий: ожидается ответ в формате json
                // фреймворк по нему определяет в каком  формате возвращать ответ (например ответ с ошибками)
                self::$headerAccept => 'application/json',
            ],
            $headers
        );

        return $this->sendRequest($method, $uri, [], [], $headers, $content);
    }

    protected function get(string $url, array $parameters = [])
    {
        return $this->requestJson('GET', $url . ($parameters ? '?' . http_build_query($parameters) : ''));
    }

    protected function post(string $uri, array $parameters = [])
    {
        return $this->requestJson('POST', $uri, $parameters);
    }

    protected function put(string $uri, array $parameters = [])
    {
        return $this->requestJson('PUT', $uri, $parameters);
    }

    protected function patch(string $uri, array $parameters = [])
    {
        return $this->requestJson('PATCH', $uri, $parameters);
    }

    protected function patchMerge(string $uri, array $parameters = [])
    {
        $headers = [self::$headerContentType => 'application/merge-patch+json'];

        return $this->requestJson('PATCH', $uri, $parameters, $headers);
    }

    protected function delete(string $uri, array $parameters = [])
    {
        return $this->requestJson('DELETE', $uri, $parameters);
    }
}
