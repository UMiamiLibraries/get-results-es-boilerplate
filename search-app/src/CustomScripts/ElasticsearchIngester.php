<?php


namespace App\CustomScripts;


class ElasticsearchIngester
{
    private $id;
    private $title;
    private $online_format;
    private $location;
    private $url;
    private $notes;
    private $thumbnail_url;

    /**
     * SearchableObject constructor.
     * @param $id
     * @param $title
     * @param $online_format
     * @param $location
     * @param $url
     * @param $notes
     * @param $thumbnail_url
     */
    public function __construct($id, $title, $online_format, $location, $url, $notes, $thumbnail_url)
    {
        $this->id = $id;
        $this->title = $title;
        $this->online_format = $online_format;
        $this->location = $location;
        $this->url = $url;
        $this->notes = $notes;
        $this->thumbnail_url = $thumbnail_url;
    }


}