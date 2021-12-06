<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient as HttpClientFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * Обертка над HttpClientInterface
 * - реализует генератор запросов последовательного получения страниц данных (пагинация).
 *
 * Пример использования:
 *      $myHttpClient = new SequenceHttpClient('https://api.site.ru');
 *
 *      $maxSequenceIdForRegions = $db->findMaxSequenceIdForRegions();
 *
 *      $myRegionsClient = $myHttpClient->create(
 *          '/my/regions',
 *          $maxSequenceIdForRegions
 *      );
 *
 *      // запрашивать "вручную"
 *      while($regionsChunk = $myRegionsClient->fetch()) {
 *          print_r(json_decode($regionsChunk, true));
 * `    }
 */
class HttpClientSequence
{
    private string $baseUrl;
    private string $relativeUrl;
    private string $method;
    private ?int $limit;
    private HttpClientInterface $httpClient;
    private int $sequenceId = 0;

    /**
     * @param string   $baseUrl           - базовый URL методов (https://api2.custon-service.com)
     * @param array    $httpClientOptions
     * @param int|null $limit             - параметр лимита записей на страницу (опционально)
     */
    public function __construct(string $baseUrl, array $httpClientOptions = [], ?int $limit = null)
    {
        $this->baseUrl = $baseUrl;
        $this->limit = $limit;
        $this->httpClient = HttpClientFactory::create($httpClientOptions);
    }

    /**
     * Фабр.метод, отдает клон текущего объекта для выполнения конкретного запроса.
     *
     * @param string $relativeUrl - относительный URL (/source/entity)
     * @param int    $sequenceId  - стартовый параметр $sequenceId
     *
     * @return HttpClientSequence
     */
    public function create(string $relativeUrl, int $sequenceId): HttpClientSequence
    {
        $this->method = 'GET';
        $this->relativeUrl = $relativeUrl;
        $this->sequenceId = $sequenceId;

        return clone $this;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Throwable
     */
    public function fetch(): string
    {
        $options = [
            'base_uri' => $this->baseUrl,
            'query' => [
                'after_id' => $this->sequenceId,
            ],
        ];
        if ($this->limit) {
            $options['query']['limit'] = $this->limit;
        }

        $response = $this->httpClient->request($this->method, $this->relativeUrl, $options);

        $sequenceId = $this->getMaxSequenceId($response);
        if ($sequenceId <= $this->sequenceId) {
            // из ответа не удалось получить следующий максимальный sequenceId (пустой ответ и т.д.)
            return '';
        }
        $this->sequenceId = $sequenceId;

        return $response->getContent();
    }

    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    /**
     * Среди items находим максимальный sequenceId
     * предполагаемая структура ответа:
     * {
     *      "items": [
     *          {
     *              "sequenceId": int // id версионирования по таблице
     *              ...
     *          },
     *          ...
     *      ]
     * }
     * или
     * [
     *      {
     *         "sequenceId": int // id версионирования по таблице
     *         ...
     *      }
     * ],
     * [
     *      ...
     * ].
     *
     * @codeCoverageIgnore
     * @throws Throwable
     */
    public function getMaxSequenceId(ResponseInterface $response): int
    {
        $sequenceId = $this->sequenceId;
        $payload = $response->toArray();

        if (!array_key_exists('items', $payload) && !empty($payload)) {
            $payload['items'] = $payload;
        }

        if (!isset($payload['items']) || !is_array($payload['items'])) {
            throw new InvalidArgumentException('The `items` body element is missing');
        }

        foreach ($payload['items'] as $item) {
            if (!isset($item['sequenceId']) || !is_int($item['sequenceId'])) {
                throw new InvalidArgumentException('The `items` element is missing a field `sequenceId`');
            }
            $sequenceId = ($sequenceId < $item['sequenceId']) ? $item['sequenceId'] : $sequenceId;
        }

        return $sequenceId;
    }
}
