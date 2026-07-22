<?php

namespace App\Components\Parser;

use Generator;
use Carbon\Carbon;
use JsonMachine\Items as JsonMachine;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use App\Components\Utility\JsonStreamWrapper;

class Google extends Parser
{
    /**
     * Parser type
     * @var string
     */
    protected $type = 'application/vnd.google-apps.spreadsheet';

    /**
     * Process
     * 
     * @return Generator
     */
    public function process(): Generator
    {
        try {
            // // Determine data source - prefer local file path, fall back to stream/string data
            // if (!empty($this->document->path) && file_exists($this->document->path)) {
            //     // Open local file as stream
            //     $fileStream = fopen($this->document->path, 'r');
            //     if (!$fileStream) {
            //         throw new \Exception('Unable to open local file: ' . $this->document->path, 500);
            //     }
            //     // Convert PHP resource to PSR-7 stream using Guzzle
            //     $data = Psr7Utils::streamFor($fileStream);
            // } elseif (is_string($this->document->data)) {
            //     // Handle string data
            //     $data = Psr7Utils::streamFor($this->document->data);
            // } else {
            //     // Use existing stream data (PSR-7 stream)
            //     $data = $this->document->data;
            // }

            // Open local file as stream
            $fileStream = fopen($this->document->path, 'r');
            if (!$fileStream) {
                throw new \Exception('Unable to open local file: ' . $this->document->path, 500);
            }
            // Convert PHP resource to PSR-7 stream using Guzzle
            $data = Psr7Utils::streamFor($fileStream);
            // Stream wrapper for clean json stream
            $streamWrapper = 'GoogleJson' . spl_object_id($data);
            // Set the PSR-7 stream
            JsonStreamWrapper::setSource($data, $streamWrapper);
            // Open native PHP stream
            $this->tmpFile = fopen(JsonStreamWrapper::getWrapper() . '://', 'r');
            // Set rows iterator
            $this->staged = $this->parseJson($this->tmpFile);
        } catch (\Exception $e) {
            // @TODO log message here
            throw $e;
        }

        // Return results
        return $this->results();
    }

    /**
     * Cleanup
     */
    public function cleanup(): void
    {
        stream_wrapper_unregister(JsonStreamWrapper::getWrapper());
    }

    protected function parseJson($stream): Generator
    {
        // Columns
        $cols = [];

        // Get items, as stream
        $items = JsonMachine::fromStream($stream, ['pointer' => '/table']);

        // Loop over all items
        foreach ($items as $key => $value) {
            // If key is cols, process columns
            if ($key === 'cols') {
                foreach ($value as $col) {
                    if (empty($col->label)) continue;
                    $cols[] = ($col->label) ? (string)$col->label : null;
                }
                if (!empty($cols)) {
                     yield $cols;
                }
            }

            // If key is rows, process rows
            if ($key === 'rows') {
                foreach ($value as $row) {
                    $row = array_map(function ($c) {
                        if (!empty($c)) {
                            // Set value from given row object
                            // Prefer the proper (type)value, then (string))value, then null
                            $v = $c->v ?? $c->f ?? null;
                            // Handle dates
                            if (str_contains($v, 'Date(')) {
                                return $this->parseDateString($v);
                            }
                            // Default
                            return $v;
                        } else {
                            return null;
                        }
                    }, $row->c);
                    yield $row;
                }
            }
        }
    }

    /**
     * Parse date string from "Date(Y, M, D)" format.
     * @param string $date
     * @return string Y-M-D formatted string
     */
    protected function parseDateString(string $date)
    {
        if (!str_contains($date, 'Date(')) {
            return $date;
        }
        $regExp = '/\(([^)]+)\)/';
        preg_match($regExp, $date, $match);
        if (!empty($match[1])) {
            // Google returns date with months indexed at 0,
            // lets convert to a Carbon object and add a month to get the indexing right
            return Carbon::createFromFormat('Y, m, d', $match[1])->addMonth(1)->format('Y-m-d');
        }
        return $date;
    }

    /**
     * Transform row
     */
    public function transformRow(mixed $row): array
    {
        return $row;
    }
}
