<?php

namespace YaPro\SymfonyHttpClientExt;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use YaPro\Helper\JsonHelper;

class ExampleClass
{
    use HttpClientExtTrait;

    private HttpClientInterface $httpClient;
    private JsonHelper $jsonHelper;

    public function __construct(HttpClientInterface $client, JsonHelper $jsonHelper)
    {
        $this->httpClient = $client;
        $this->jsonHelper = $jsonHelper;
    }

    // it is very important to make the getHttpClient method, because it is used in HttpClientTrait
    protected function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    // it is very important to make the getJsonHelper method (if you wish to use HttpClientJsonExtTrait)
    protected function getJsonHelper(): JsonHelper
    {
        return $this->jsonHelper;
    }
}
