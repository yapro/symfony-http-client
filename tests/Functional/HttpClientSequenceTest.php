<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt\Tests\Functional;

use YaPro\Helper\LiberatorTrait;
use YaPro\SymfonyHttpClientExt\HttpClientSequence;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpClientSequenceTest extends TestCase
{
    use LiberatorTrait;
    
    private HttpClientInterface $httpClientMock;
    private ResponseInterface $responseMock;
    private static HttpClientSequence $httpClientSequence;

    public static function setUpBeforeClass(): void
    {
        $httpClientSequenceFactory = new HttpClientSequence('https://exaple.loc');
        self::$httpClientSequence = $httpClientSequenceFactory->create('/entity/one', 0);
    }

    /**
     * 1 запрос, получены корректные данные
     * - продолжаем итерацию запросов.
     */
    public function testFetchSuccessResponse1(): void
    {
        $responseRawBody = '{"items":[{"sequenceId":10,"content":"ABCabc example 4"},{"sequenceId":5,"content":"ABCabc example 5"}]}';
        $mockHttpClient = new MockHttpClient(new MockResponse($responseRawBody));
        $this->setClassPropertyValue(self::$httpClientSequence, 'httpClient', $mockHttpClient);

        $this->assertEquals($responseRawBody, self::$httpClientSequence->fetch());
        $this->assertEquals(10, self::$httpClientSequence->getSequenceId());
    }

    /**
     * 2 запрос, получены корректные данные
     * - продолжаем итерацию запросов.
     *
     * @depends testFetchSuccessResponse1
     */
    public function testFetchSuccessResponse2(): void
    {
        $responseRawBody = '{"items":[{"sequenceId":11,"content":"ABCabc example 1"},{"sequenceId":51,"content":"ABCabc example 5"}]}';
        $mockHttpClient = new MockHttpClient(new MockResponse($responseRawBody));
        $this->setClassPropertyValue(self::$httpClientSequence, 'httpClient', $mockHttpClient);

        $this->assertEquals($responseRawBody, self::$httpClientSequence->fetch());
        $this->assertEquals(51, self::$httpClientSequence->getSequenceId());
    }

    /**
     * 3 запрос, получены корректные данные
     * - продолжаем итерацию запросов.
     *
     * @depends testFetchSuccessResponse2
     */
    public function testFetchSuccessResponse3(): void
    {
        $responseRawBody = '[{"sequenceId":55,"content":"ABCabc example 1"},{"sequenceId":66,"content":"ABCabc example 5"}]';
        $mockHttpClient = new MockHttpClient(new MockResponse($responseRawBody));
        $this->setClassPropertyValue(self::$httpClientSequence, 'httpClient', $mockHttpClient);

        $this->assertEquals($responseRawBody, self::$httpClientSequence->fetch());
        $this->assertEquals(66, self::$httpClientSequence->getSequenceId());
    }

    /**
     * 4 запрос, получен "Пустой ответ"
     * - итерация запросов прекращается - НЕ удалось определить следующий sequenceId
     * - метод fetch() вернет пустую строку вместо тела ответа.
     *
     * @depends testFetchSuccessResponse3
     */
    public function testFetchEmptyResponse(): void
    {
        $responseRawBody = '{"items":[]}';
        $mockHttpClient = new MockHttpClient(new MockResponse($responseRawBody));
        $this->setClassPropertyValue(self::$httpClientSequence, 'httpClient', $mockHttpClient);

        $this->assertEquals('', self::$httpClientSequence->fetch());
        $this->assertEquals(66, self::$httpClientSequence->getSequenceId());
    }
}
