<?php

declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Entity\News;
use Elasticsearch\Client;

class ElasticsearchService
{
    private const INDEX_NAME = 'news';
    private const INDEX_LIMIT = 100;
    private const RESULT_LIMIT = 10;

    /** @var Client $client */
    private Client $client;
    
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    private function getNewsIdsFromResult(array $result): array
    {
        if (empty($result['hits']) || empty($result['hits']['hits'])) {
            return [];
        }

        $ids = [];
        $hits = $result['hits']['hits'];
        foreach ($hits as $hit) {
            $ids[] = $hit['_id'];
        }

        return $ids;
    }

    /**
     * @param string $phrase
     * @return array
     */
    public function searchNewsByPhrase(string $phrase): array
    {
        $result = $this->client->search([
            'index' => self::INDEX_NAME,
            'size' => self::RESULT_LIMIT,
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $phrase,
                        'fields' => ['title', 'description'],
                        'type' => 'best_fields',
                    ]
                ]
            ]
        ]);

        return $this->getNewsIdsFromResult($result);
    }

    /**
     * @param array|News[] $news
     */
    public function indexNews(array $news): void
    {
        $count = 0;
        $params = ['body' => []];

        /** @var News $item */
        foreach ($news as $item) {
            $count++;

            $params['body'][] = [
                'index' => [
                    '_index' => self::INDEX_NAME,
                    '_id' => $item->getId(),
                ]
            ];

            $params['body'][] = [
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
            ];

            if ($count >= self::INDEX_LIMIT) {
                $this->client->bulk($params);

                $count = 0;
                $params = ['body' => []];
            }
        }

        //Отправить последнюю партию, если она существует
        if (!empty($params['body'])) {
            $this->client->bulk($params);
        }
    }
}
