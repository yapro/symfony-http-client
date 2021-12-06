<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use YaPro\SymfonyHttpClientExt\HttpClientSequence;

class HttpClientSequenceMethodTest extends TestCase
{
    /**
     * Провайдер корректных значений.
     *
     * @return array[]
     */
    public function providerGetMaxSequenceIdPositive(): array
    {
        return [
            [
                'rawBody' => '{"items":[{"sequenceId":10,"content":"ABCabc example 4"},{"sequenceId":5,"content":"ABCabc example 5"}]}',
                'expectedMaxSequenceId' => 10,
            ],
            [
                'rawBody' => '{"items":[{"sequenceId":11,"content":"ABCabc example 1"},{"sequenceId":51,"content":"ABCabc example 5"}]}',
                'expectedMaxSequenceId' => 51,
            ],
            [
                'rawBody' => '{"items":[]}',
                'expectedMaxSequenceId' => 0,
            ],
        ];
    }

    /**
     * Провайдер некорректных значений.
     *
     * @return array[]
     */
    public function providerGetMaxSequenceIdNegative(): array
    {
        return [
            [
                'rawBody' => '[]',
                'expectException' => \InvalidArgumentException::class,
            ],
            [
                'rawBody' => '',
                'expectException' => JsonException::class,
            ],
        ];
    }

    /**
     * @dataProvider providerGetMaxSequenceIdPositive
     */
    public function testGetMaxSequenceIdPositive(string $rawBody, int $expectedMaxSequenceId): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse($rawBody));
        $responseMock = $mockHttpClient->request('GET', 'https://exaple.loc');
        $httpClientSequence = new HttpClientSequence('https://exaple.loc');

        $this->assertEquals($expectedMaxSequenceId, $httpClientSequence->getMaxSequenceId($responseMock));
    }

    /**
     * @dataProvider providerGetMaxSequenceIdNegative
     */
    public function testGetMaxSequenceIdNegative(string $rawBody, string $expectException): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse($rawBody));
        $responseMock = $mockHttpClient->request('GET', 'https://exaple.loc');
        $httpClientSequence = new HttpClientSequence('https://exaple.loc');

        $this->expectException($expectException);
        $httpClientSequence->getMaxSequenceId($responseMock);
    }
}
