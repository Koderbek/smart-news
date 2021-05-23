<?php

declare(strict_types=1);

namespace App\Services\Elasticsearch;

use App\Services\ImplementFactoryInterface;
use Elasticsearch\ClientBuilder;

class ElasticsearchServiceFactory implements ImplementFactoryInterface
{
    private const ELASTICSEARCH_URL = 'http://localhost:9200';

    /**
     * @return ElasticsearchService
     */
    public static function createService(): ElasticsearchService
    {
        $client = ClientBuilder::create()
            ->setHosts([self::ELASTICSEARCH_URL])
            ->build();

        return new ElasticsearchService($client);
    }
}
