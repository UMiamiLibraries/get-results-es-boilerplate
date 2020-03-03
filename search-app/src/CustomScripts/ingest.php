<?php

require __DIR__ . '/../../vendor/autoload.php';

use \App\CustomScripts\ElasticsearchIngester;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');

$apiUrl = 'https://www.loc.gov/collections/national-screening-room/?fo=json';

$elasticSearchIngester = new ElasticsearchIngester($apiUrl, 'screening-room-index');

if (!$elasticSearchIngester->indexExists()) {
    $elasticSearchIngester->createIndex();
}

$elasticSearchIngester->ingestData();



