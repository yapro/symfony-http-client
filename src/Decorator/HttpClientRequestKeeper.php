<?php
declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt\Decorator;

use phpDocumentor\Reflection\Types\This;
use ReflectionClass;
use Symfony\Component\HttpClient\CurlHttpClient;
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
    
    // удобно смотреть в логах данные, которые еще не обработаны json_encode, http_build_query и т.п. способами
    public function getRequestData(): array
    {
        return [
            'get_params' => $this->options['query'] ?? [],
            'body_json' => $this->options['json'] ?? [],
            'body_raw' => $this->getBody(),
        ];
    }

    // удобно взять себе curl команду и выполнить, чтобы убедиться самостоятельно в проблеме 
    public function getCurlCommand(): string
    {
        $client = $this->client;
        while ($client !== null) {
            if (!$this->hasProperty($client, 'client')) {
                break;
            }
            $client = $this->getClassPropertyValue($client, 'client');
            if ($client instanceof ScopingHttpClient) {
                break;
            }
        }
        if (!$client instanceof ScopingHttpClient) {
            return 'Your client is configured incorrectly. You may have a problem with dependency injection. Try using '.
            'the correct $variable name of HttpClientInterface in your class __construct. And then remove the cache: rm -rf var/cache/*';
        }
        $site = '';
        $query = '';
        $headers = [];
        $body = $this->getBody();
        $defaultOptionsByRegexp = $this->getClassPropertyValue($client, 'defaultOptionsByRegexp');
        $defaultRegexp = $this->getClassPropertyValue($client, 'defaultRegexp');
        $site = $defaultOptionsByRegexp[$defaultRegexp]['base_uri'];
        $headers = $defaultOptionsByRegexp[$defaultRegexp]['headers'];
        $authBasic = $defaultOptionsByRegexp[$defaultRegexp]['auth_basic'] ?? '';
        if ($authBasic && !isset($headers['Authorization'])) {
            $headers['Authorization'] = 'Basic '. base64_encode($authBasic);
        }
        if (isset($this->options['query'])) {
            $query = '?' . http_build_query($this->options['query']);
        }
        if (isset($this->options['json'])) {
            $body = self::jsonEncode($this->options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->curlConverter->getCurlCommand($this->method, $site . $this->url . $query, $body, $headers);
    }
    
    private function getBody(): string
    {
        if (!isset($this->options['body'])) {
            return '';
        }
        if (!is_string($this->options['body'])) {
            return 'HttpClientRequestKeeper unsupported body type: ' . gettype($this->options['body']);
        }
        
        return $this->options['body'];
    }

    private function hasProperty($object, $propertyName): bool
    {
        $class = new ReflectionClass($object);
        while (!$class->hasProperty($propertyName) && $class->getParentClass() !== false) {
            $class = $class->getParentClass();
        }

        return $class->hasProperty($propertyName);
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
