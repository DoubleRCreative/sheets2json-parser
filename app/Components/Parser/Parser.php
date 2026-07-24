<?php

namespace App\Components\Parser;

use Exception;
use Generator;
use Illuminate\Support\Facades\Log;
use App\Components\Document\Document;
use App\Components\Document\DocumentException;

abstract class Parser
{
    /**
     * Document model
     */
    protected $document;

    /**
     * Parser type
     */
    protected $type;

    /**
     * Parser headers
     */
    protected $headers;

    /**
     * Parser options
     * @var array
     */
    protected $options = [];

    /**
     * Tmp file
     */
    protected $tmpFile;

    /**
     * Reader
     */
    protected $reader;

    /**
     * Staged data
     * Data array for use while parsing documents
     * @var iterator
     */
    protected $staged;

    /**
     * Class constructor
     * 
     * @param Document $Document - document model instance
     */
    public function __construct(Document $Document, array $options = [])
    {
        // Set document model
        $this->document = $Document;
        // Set desired options
        $this->options = array_merge($this->document->options, $options);
        // Validate parser type
        if (!$this->validate()) {
            Log::error('Invalid document for parser', ['type' => $this->type]);
            throw new \Exception('Invalid document for parser type=' . $this->type);
        }
        // Verify that document path exists
        if (!file_exists($this->document->path)) {
            Log::error('Document not found for parser', ['type' => $this->type]);
            throw new \Exception('Document not found for parser type=' . $this->type);
        }
    }

    /**
     * Process method
     * All child classes will require this method,
     * it should process the provided file content into an array
     * (ie. CSV, Google Doc, or XLS files)
     * 
     */
    abstract public function process(): Generator;

    /**
     * Cleanup method
     * All child classes will require this method,
     * use this method to do any clean up processes that
     * may be needed for a given parser class (ie, delete tmp files)
     */
    abstract public function cleanup(): void;

    /**
     * Transform row
     * Abstract method to allow each parser class to decide if and how
     * to transform each item/row from the document
     */
    abstract public function transformRow(mixed $row): array;

    /**
     * Results
     * Combine data and headers into a single iterator object. This method will
     * essentially take each data item and add keys to each value
     * based on the supplied headers array to build a final key/value 
     * associative array
     * 
     * @return Generator (Iterator object)
     */
    public function results(): Generator
    {
        // Set external index tracking
        $index = 0;
        $offset = $this->options[Document::OPTION_OFFSET] ?? 0;

        // Track total size in bytes
        $maxSize = $this->options[Document::OPTION_SIZE];

        try {
            // Foreach item (row) in data array
            foreach ($this->staged as $idx => $item) {
                $index++;

                $item = $this->transformRow($item);
                if (!is_array($item)) {
                    $item = $item->toArray();
                }

                // Skip rows before offset
                if ($offset > 0 && $index <= $offset) {
                    continue;
                }

                // After skipping offset, this is the first row for header processing
                if ($index == ($offset + 1)) {
                    $this->setHeaders($item);
                    if (!empty($this->headers)) continue;
                }

                // Track first/last counts from document
                $this->setFirstLast($index);

                // Total records
                $this->document->data_total++;

                // Range
                if (!empty($this->options[Document::OPTION_RANGE])) {
                    $range = Document::getRange($this->options[Document::OPTION_RANGE]);
                    if ($index < ((int)$range[0]) || $index > ((int)$range[1])) continue;
                }

                // If document record limit is reached, break and return results
                if ($this->document->data_count >= $this->options[Document::OPTION_LIMIT]) {
                    $this->document->error = new DocumentException(DocumentException::DATA_LIMIT, $this->document);
                    continue;
                }

                // Check data size limits
                if ($this->document->data_size > $maxSize) {
                    $this->document->error = new DocumentException(DocumentException::DATA_SIZE, $this->document);
                    continue;
                }

                // If headers exists
                if (!empty($this->headers)) {
                    // New item array
                    $newItem = [];
                    // For each value in item
                    foreach ($item as $idx => $value) {
                        // Get related header, normalized
                        // If no header, use default fallback
                        $header = !empty($this->headers[$idx]) ? $this->headers[$idx] : 'column_' . ($idx + 1);
                        // Normalize headers, should return array so always get first item...
                        // $header = $this->normalizeHeaders($header)[0];
                        // Set new item key/value
                        // If value is empty, use null
                        $newItem[$header] = ($value !== '') ? trim($value) : null;
                    }

                    // Limit by specific columns/keys
                    if (!empty($this->options[Document::OPTION_COLUMNS]) && !empty($this->headers)) {
                        $columns = array_map('trim', explode(',', $this->options[Document::OPTION_COLUMNS]));
                        $columns = $this->normalizeHeaders($columns);

                        // Check to make sure desired columns exist
                        // return early if we determine none of the requested columns exist in the dataset
                        if (empty(array_intersect($this->headers, $columns))) {
                            continue;
                        }

                        $newItem = array_intersect_key($newItem, array_flip($columns));
                    }

                    // Reset current item
                    $item = $newItem;
                }

                // Skip empty row
                if ($this->options[Document::OPTION_SKIP_EMPTY] ?? false) {
                    if (empty(array_filter($item))) {
                        continue; // Skip row if contains no values
                    }
                }

                // Calculate size of current item
                $itemSize = mb_strlen(json_encode($item), '8bit');

                // Transform item to include metadata
                $item = [
                    'data' => $item,
                    'metadata' => [
                        'index' => $index,
                        'size' => $itemSize
                    ]
                ];

                // Yield item
                yield $item;

                // Track from/to processed rows
                $this->setFromTo($index);
                // Increase count
                $this->document->data_count++;
                // Update total size and yield item
                $this->document->data_size += $itemSize;
            }
        } catch (\Exception $e) {
            // TODO: Log message here
            $this->cleanup();
            // Rethrow error
            throw $e;
        } finally {
            // Cleanup
            $this->cleanup();
        }
    }

    /**
     * Normalize headers
     * Simple method to format strings in a certain way.
     * Will return either an array of normalized strings
     * 
     * @param array $headers
     * @return array
     */
    protected function normalizeHeaders(array $headers): array
    {
        // Foreach item in array
        foreach ($headers as $i => $header) {
            // Remove spaces
            $headers[$i] = str_replace(' ', '_', $header);
            // Remove hyphens
            $headers[$i] = str_replace('-', '_', $headers[$i]);
            // Replace periods
            $headers[$i] = str_replace('.', '_', $headers[$i]);
            // Force lower case
            // $headers[$i] = strtolower($headers[$i]);
        }
        // Return normalized array
        return $headers;
    }

    /**
     * Validate
     * Validate document and parser type match
     * 
     * @return bool
     */
    protected function validate(): bool
    {
        if ($this->document->type !== $this->type) {
            return false;
        }
        return true;
    }

    /**
     * Get Headers
     * 
     * @return array
     */
    protected function setHeaders(array $headers): array
    {
        // If headers option exists
        if (!empty($this->options[Document::OPTION_HEADERS])) {
            // Headers is true/1
            if ($this->options[Document::OPTION_HEADERS] == '1') {
                // Get first item in array
                $this->headers = $headers;
            } else {
                // Normalize header string
                // $this->headers = $this->normalizeHeaders($this->options[Document::OPTION_HEADERS]);
            }
        }

        // Normalize headers
        if (!empty($this->headers)) {
            $this->headers = $this->normalizeHeaders($this->headers);
        }

        // Return array
        return $this->headers ?? [];
    }

    /**
     * Set first and last total items
     * 
     * @var int $index
     */
    protected function setFirstLast(int $index): void
    {
        if (empty($this->document->data_first)) {
            $this->document->data_first = $index;
        }
        $this->document->data_last = $index;
    }

    /**
     * Set from and to processed items
     * 
     * @var int $index
     */
    protected function setFromTo(int $index): void
    {
        if (empty($this->document->data_from)) {
            $this->document->data_from = $index;
        }
        $this->document->data_to = $index;
    }
}
