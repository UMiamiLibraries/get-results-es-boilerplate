<?php

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Elasticsearch\ClientBuilder;
use \App\CustomScripts\ElasticsearchIngester;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');

$apiUrl = $_ENV['SOURCE_API_URL'];
$elastic_host_info = [
    $_ENV['ELASTIC_HOST'],
    $_ENV['ELASTIC_PORT'],
    $_ENV['ELASTIC_USER'],
    $_ENV['ELASTIC_PASSWORD']
];
$elasticSearchClient = ClientBuilder::create()->setHosts($elastic_host_info)->build();
$data = [];

function getJsonData($url)
{
    $json = file_get_contents($url);
    return json_decode($json);
}

function indexExists($elasticSearchClient)
{
    $indexParams['index'] = 'screening-room-index';
    return $elasticSearchClient->indices()->exists($indexParams);
}

function createIndex($elasticSearchClient)
{
    $params = [
        'index' => 'screening-room-index',
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 2,
            ],
        ]
    ];

    $elasticSearchClient->indices()->create($params);
}

if (!indexExists($elasticSearchClient)) {
    createIndex($elasticSearchClient);
}

do {
    sleep(1);
    $data = getJsonData($apiUrl);

    if ($data) {
        $searchableObjects = [];
        $results = $data->results;
        $pagination = $data->pagination;

        $params = ['body' => []];

        foreach ($results as $result) {
            $params['body'][] = [
                'index' => [
                    '_index' => 'screening-room-index',
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

        $elasticSearchClient->bulk($params);

        $apiUrl = !empty($pagination->next) ? $pagination->next : null;
    }

} while (!empty($apiUrl));