<?php

namespace Tests\Unit\Parser;

use App\Components\Parser\Google;
use App\Components\Document\Document;

class GoogleTest extends ParserTest
{

    /**
     * Setup
     */
    public function setUp(): void
    {
        // Get test data
        $this->source = './tests/Data/Files/google.txt';
        $this->type = 'application/vnd.google-apps.spreadsheet';
        // Setup
        parent::setUp();
    }

    /**
     * Parse google doc
     */
    public function test_google_doc_parser(): void
    {
        // Init parser
        $parser = new Google($this->document);
        // Process parser
        $data = $parser->process();

        // Assert values
        $this->assertNotNull($data);
        $this->assertInstanceOf('Generator', $data);

        // Convert data to array
        $data = iterator_to_array($data);
        // Header headers from first item keys
        $headers = array_keys($data[0]);

        // Assert array results
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('Row_1_Value_1', $headers[0]);
        $this->assertCount(5, $headers);
    }

    /**
     * Parse google doc with options
     */
    public function test_google_doc_parser_with_options(): void
    {
        // Init parser
        $parser = new Google($this->document, [Document::OPTION_HEADERS => 0]);
        // Process parser
        $data = $parser->process();

        // Assert values
        $this->assertNotNull($data);
        $this->assertInstanceOf('Generator', $data);

        // Convert data to array
        $data = iterator_to_array($data);
        // Header headers from first item keys
        $headers = array_keys($data[0]);

        // Assert array results
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('Row 1 Value 1', $data[0][0]);
        $this->assertNotEquals('Row_1_Value_1', $headers[0]);
    }
}
