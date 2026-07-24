<?php

namespace Tests\Unit\Parser;

use App\Components\Parser\Csv;
use App\Components\Document\Document;
use Generator;

class CsvTest extends ParserTest
{

    /**
     * Setup
     */
    public function setUp(): void
    {
        // Get test data
        $this->source = './tests/Data/Files/headers.csv';
        $this->type = 'text/csv';
        // Setup
        parent::setUp();
    }

    /**
     * A basic test example.
     */
    public function test_csv_parser(): void
    {
        // Init parser
        $parser = new Csv($this->document);
        // Process parser
        $data = $parser->process();
        
        // Assert data
        $this->assertNotNull($data);
        $this->assertInstanceOf('Generator', $data);

        // Convert data to array
        $data = iterator_to_array($data);
        // Header headers from first item keys
        $headers = array_keys($data[0]['data']);
        
        // Assert array results
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('header_1', $headers[0]);
        $this->assertCount(5, $headers);
    }

    /**
     * A basic test example.
     */
    public function test_csv_parser_with_options(): void
    {
        // Init parser
        $parser = new Csv($this->document, [Document::OPTION_HEADERS => 0]);
        // Process parser
        $data = $parser->process();

        // Assert values
        $this->assertNotNull($data);
        $this->assertInstanceOf('Generator', $data);

        // Convert data to array
        $data = iterator_to_array($data);
        // Header headers from first item keys
        $headers = array_keys($data[0]['data']);
        
        // Assert array results
        $this->assertIsArray($data);
        $this->assertCount(5, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('header 1', $data[0]['data'][0]);
        $this->assertNotEquals('header_1', $headers[0]);
    }
}
