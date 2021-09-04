<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

/**
 * Based on https://symfony.com/doc/current/components/http_client.html
 *
 * @see https://speakerdeck.com/nicolasgrekas/symfony-httpclient-what-else
 */
trait HttpClientExtTrait
{
    private static string $headerContentType = 'content_type';
    private static string $headerAccept = 'Accept';

    /**
     * Трансформировать переменную $headers в переменную $server
     */
    protected function getServerParametersFromHeaderParameters(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            // Section 3.1 of RFC 822 [9] : Field names are case-insensitive https://stackoverflow.com/questions/7718476
            $key = mb_strtolower($key);
            // delete the prefix that added by php for headers in $_SERVER variable:
            if (mb_strpos($key, 'http_') === 0) {
                $key = mb_substr($key, 5);
            }
            // FIX: в vendor/symfony/http-foundation/Request.php:379 добавляется ключ 'CONTENT_TYPE' (если его нет) и
            // затем в vendor/symfony/http-foundation/ServerBag.php:35 ключ без префикса заменяет ключ с префиксом HTTP_
            if ($key !== self::$headerContentType) {
                $key = 'http_' . $key;
            }
            $key = str_replace('-', '_', mb_strtoupper($key));
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Обертка, чтобы автоматом трансформировать переменную $headers в переменную $server
     */
    protected function sendRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $headers = [],
        string $content = null,
        bool $changeHistory = true
    ) {
        $server = $this->getServerParametersFromHeaderParameters($headers);

        return $this->getHttpClient()->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }
}
