<?php

namespace App\Components\Http;

use App\Components\Http\Client;

abstract class HttpService
{
    protected $src;
    protected $options;
    protected Client $client;
   
    abstract public function get();

    protected function client(): Client
    {
        return $this->client = new Client($this->src);
    }
}