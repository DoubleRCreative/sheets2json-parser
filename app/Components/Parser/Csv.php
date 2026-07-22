<?php

namespace App\Components\Parser;

use Generator;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * CSV file class
 * This class handles the specific logic for working with CSV (comma separated values) files and their data
 */
class Csv extends Parser
{
    /**
     * Parser type
     * @var string
     */
    protected $type = 'text/csv';

    /**
     * Process
     * 
     * @return Generator
     */
    public function process(): Generator
    {

        try {
            // // Get contents of document 
            // $stream = $this->document->data;
            // // Set up stream/file data
            // // $this->tmpFile = fopen('php://temp/maxmemory:5242880', 'r+'); // 5 MB in memory, then disk
            // // stream_copy_to_stream($stream->detach(), $this->tmpFile);
            // // rewind($this->tmpFile);
            // $this->tmpFile = tempnam(sys_get_temp_dir(), 'csv_');
            // $outputStream = fopen($this->tmpFile, 'w+');
            // stream_copy_to_stream($stream->detach(), $outputStream);
            // fclose($outputStream);
            
            // Process file content
            $this->reader = Reader::from($this->document->path);
            //$reader->seteaderOffset(0); // remove headers
            $this->reader->skipEmptyRecords(); // skip empty records
            // Return file records
            $this->staged = (new Statement())->process($this->reader);
        } catch (\Exception $e) {
            // @TODO log message here
            $this->cleanup();
            throw $e;
        }

        // Return data
        return $this->results();
    }

    public function cleanup(): void
    {
        //unlink($this->tmpFile);
    }

    /**
     * Transform row
     */
    public function transformRow(mixed $row): array
    {
        return $row;
    }
}
