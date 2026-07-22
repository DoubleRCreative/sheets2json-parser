<?php

namespace Tests\Unit\Parser;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use App\Components\Document\Document;

class ParserTest extends TestCase
{
    protected Document $document;
    protected string $source;
    protected string $type;
    protected array $options = [];

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        // Set options
        $this->options = array_merge([
            Document::OPTION_HEADERS => 1, // Default headers: true
            Document::OPTION_LIMIT => 1000, // Default record limit
            Document::OPTION_SIZE => (1000*1024), // Default 1MB
            Document::OPTION_RANGE => null, // Default all rows
            Document::OPTION_SKIP_EMPTY => true
        ], $this->options);
        // // Get test data
        // $data = file_get_contents($this->source);
        // $stream = Utils::streamFor($data);
        // Create faux document
        $this->document = new Document([
            //'data' => $stream,
            'path' => $this->source,
            'type' => $this->type,
            'source' => $this->source,
            'options' => $this->options
        ]);
    }
}
