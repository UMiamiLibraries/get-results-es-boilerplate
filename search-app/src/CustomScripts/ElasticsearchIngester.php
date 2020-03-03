<?php

namespace App\CustomScripts;

use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
require __DIR__ . '/../../vendor/autoload.php';

class ElasticsearchIngester
{
    private $apiUrl;
    private $elasticSearchClient;
    private $indexName;

    /**
     * ElasticsearchIngester constructor.
     * @param $apiUrl
     * @param $indexName
     */
    public function __construct($apiUrl, $indexName)
    {

        $this->apiUrl = $apiUrl;
        $this->indexName = $indexName;
        $elastic_host_info = [
            $_ENV['ELASTIC_HOST'],
            $_ENV['ELASTIC_PORT'],
            $_ENV['ELASTIC_USER'],
            $_ENV['ELASTIC_PASSWORD']
        ];
        $this->elasticSearchClient = ClientBuilder::create()->setHosts($elastic_host_info)->build();
    }


    private function getJsonData($url)
    {
        sleep(1); //We are waiting a second between each API call to prevent being temporarily banned by the Library of Congress API
        $json = file_get_contents($url);
        return json_decode($json);
    }

    public function indexExists()
    {
        $indexParams['index'] = $this->indexName;
        return $this->elasticSearchClient->indices()->exists($indexParams);
    }

    public function createIndex()
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 2,
                ],
            ]
        ];

        $this->elasticSearchClient->indices()->create($params);
    }

    public function ingestData()
    {
        $progressBar = new ProgressBar(new ConsoleOutput(), 100);
        echo 'Ingesting data' . PHP_EOL;

        $currentApiUrl = $this->apiUrl;
        do {
            $data = $this->getJsonData($currentApiUrl);

            if ($data) {
                $results = $data->results;
                $pagination = $data->pagination;

                $params = $this->prepareValues($results);

                $this->elasticSearchClient->bulk($params);
                $progressBar->advance(4);

                $currentApiUrl = !empty($pagination->next) ? $pagination->next : null;
            }

        } while (!empty($currentApiUrl));


        $progressBar->finish();
        echo PHP_EOL . 'Done ingesting data' . PHP_EOL;


    }

    private function prepareValues($results)
    {
        $params = ['body' => []];

        foreach ($results as $result) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_id' => $result->id
                ]
            ];

            $params['body'][] = [
                'title' => $result->title,
                'online_format' => $result->online_format,
                'location' => $result->location,
                'url' => $result->url,
                'notes' => $result->item->notes,
                'image_url' => $result->image_url
            ];
        }

        return $params;
    }

}