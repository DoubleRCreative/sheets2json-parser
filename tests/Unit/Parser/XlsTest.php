<?php

namespace Tests\Unit\Parser;

use App\Components\Parser\Xls;
use App\Components\Document\Document;

class XlsTest extends ParserTest
{

    /**
     * Setup
     */
    public function setUp(): void
    {
        // Get test data
        $this->source = './tests/Data/Files/headers.xlsx';
        $this->type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        // Setup
        parent::setUp();
    }

    /**
     * Parse xlsx
     */
    public function test_xls_parser(): void
    {
        // Init parser
        $parser = new Xls($this->document);
        // Process parser
        $data = $parser->process();

        // Assert data
        $this->assertNotNull($data);
        $this->assertInstanceOf('Generator', $data);

        // Convert data to array
        $data = iterator_to_array($data);
        // Header headers from first item keys
        $headers = array_keys($data[1]['data']);

        // Assert array results
        $this->assertIsArray($data);
        $this->assertCount(20, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('_id', $headers[0]);
        $this->assertCount(14, $headers);
    }

    /**
     * Parse xlsx with options
     */
    public function test_xls_parser_with_options(): void
    {
        // Init parser
        $parser = new Xls($this->document, [Document::OPTION_HEADERS => 0]);
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
        $this->assertCount(21, $data);
        $this->assertNotNull($headers);
        $this->assertEquals('_id', $data[0]['data'][0]);
        $this->assertNotEquals('_id', $headers[0]);
    }
}
