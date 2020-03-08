<?php

namespace App\Command;

use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

class ElasticsearchIngesterCommand extends Command
{
    protected static $defaultName = 'app:ingest-data';
    private $elasticSearchClient;
    private $outputInterface;
    private $apiUrl;
    private $indexName;

    public function __construct()
    {
        $elastic_host_info = [
            $_ENV['ELASTIC_HOST'],
            $_ENV['ELASTIC_PORT'],
            $_ENV['ELASTIC_USER'],
            $_ENV['ELASTIC_PASSWORD']
        ];
        $this->elasticSearchClient = ClientBuilder::create()->setHosts($elastic_host_info)->build();

        parent::__construct();

    }

    protected function configure()
    {
        $this->setDescription('Ingests data from a LOC Collection API into an Elasticsearch cluster')
            ->addArgument('apiUrl', InputArgument::REQUIRED, 'Pass API Url')
            ->addArgument('indexName', InputArgument::REQUIRED, 'Pass the index name');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->outputInterface = $output;
        $this->apiUrl = $input->getArgument('apiUrl');
        $this->indexName = $input->getArgument('indexName');

        $output->writeln('Checking if index exists...');
        if (!$this->indexExists()) {
            $output->writeln('Creating index ' . $this->indexName);
            $this->createIndex();
        }

        $this->ingestData();
        return 0;
    }

    private function getJsonData($url)
    {
        sleep(1); //We are waiting a second between each API call to prevent being temporarily banned by the Library of Congress API
        $json = file_get_contents($url);
        return json_decode($json);
    }

    private function indexExists()
    {
        $indexParams['index'] = $this->indexName;
        return $this->elasticSearchClient->indices()->exists($indexParams);
    }

    private function createIndex()
    {
        $params = [
            'index' => $this->indexName
        ];

        $this->elasticSearchClient->indices()->create($params);
    }

    private function ingestData()
    {
        $this->outputInterface->writeln('Ingesting data');
        $progressBar = new ProgressBar($this->outputInterface, 100);

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
        $this->outputInterface->writeln( PHP_EOL . 'Finished ingesting data');
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
                'title' => isset($result->title) ? $result->title : '',
                'online_format' => isset($result->online_format) ? $result->online_format : '',
                'location' => isset($result->location) ? $result->location : '',
                'url' => isset($result->url) ? $result->url : '',
                'notes' => isset($result->item->notes) ? $result->item->notes : '',
                'image_url' => isset($result->image_url) ? $result->image_url : '',
                'description' => isset($result->description) ? $result->description : ''
            ];
        }

        return $params;
    }

}
