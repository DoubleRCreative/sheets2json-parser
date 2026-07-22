<?php

namespace App\Components\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Client
{
    protected string $url;
    protected $options = [];
    protected $headers = [];

    public function __construct(string $url = null)
    {
        $this->url = $url;
    }

    protected function getUrl()
    {
        return $this->url;
    }

    public function get(string $url = null): Response
    {
        return Http::withHeaders($this->headers)->withOptions($this->options)->get(($url ?? $this->getUrl()));
    }

    public function getStream(): Response
    {
        return $this->setOptions(['stream' => true])->get($this->getUrl());
    }

    /**
     * Set Options
     * Allows the passage of Guzzle options into the Http facade
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Set Headers
     * Set HTTP headers for a given request
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Parse url
     */
    public function parseUrl(): ?array
    {
        return parse_url($this->url);
    }

    /**
     * Get query parameters from url
     */
    public function getQuery(): ?array
    {
        $query = [];
        $parts = $this->parseUrl();
        if (!empty($parts['query'])) {
            parse_str($parts['query'] ?? '', $query);
        }
        return $query;
    }
}