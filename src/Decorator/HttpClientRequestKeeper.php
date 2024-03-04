<?php
declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt\Decorator;

use ReflectionClass;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ResetInterface;
use YaPro\SymfonyHttpClientExt\SymfonyRequestToCurlCommandConverter;

class HttpClientRequestKeeper implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait;

    private HttpClientInterface $client;
    private SymfonyRequestToCurlCommandConverter $curlConverter;

    private string $method;
    private string $url;
    private array $options;

    public function __construct(
        HttpClientInterface $decoratedClient,
        SymfonyRequestToCurlCommandConverter $curlConverter
    ) {
        $this->client = $decoratedClient;
        $this->curlConverter = $curlConverter;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->method = $method;
        $this->url = $url;
        $this->options = $options;
        return $this->client->request($method, $url, $options);
    }

    public function getCurlCommand(): string
    {
        $client = $this->client;
        while (!$client instanceof ScopingHttpClient) {
            $client = $this->getClassPropertyValue($client, 'client');
        }
        $defaultOptionsByRegexp = $this->getClassPropertyValue($client, 'defaultOptionsByRegexp');
        $defaultRegexp = $this->getClassPropertyValue($client, 'defaultRegexp');
        $site = $defaultOptionsByRegexp[$defaultRegexp]['base_uri'];
        $headers = $defaultOptionsByRegexp[$defaultRegexp]['headers'];
        $body = '';
        if (isset($this->options['json'])) {
            $body = self::jsonEncode($this->options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (isset($this->options['body'])) {
            $body = $this->options['body'];
        }

        return $this->curlConverter->getCurlCommand($this->method, $site . $this->url, $body, $headers);
    }

    // copied from \YaPro\Helper\LiberatorTrait::getClassPropertyValue
    private function getClassPropertyValue($object, $propertyName)
    {
        $class = new ReflectionClass($object);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    // copied from \Symfony\Component\HttpClient\HttpClientTrait::jsonEncode
    private function jsonEncode(mixed $value, ?int $flags = null, int $maxDepth = 512): string
    {
        $flags ??= \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_PRESERVE_ZERO_FRACTION;

        try {
            $value = json_encode($value, $flags | \JSON_THROW_ON_ERROR, $maxDepth);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Invalid value for "json" option: '.$e->getMessage());
        }

        return $value;
    }
}
