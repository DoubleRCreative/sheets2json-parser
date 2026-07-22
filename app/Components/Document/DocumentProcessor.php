<?php

namespace App\Components\Document;

use App\Components\Parser\Csv;
use App\Components\Parser\Xls;
use App\Components\Parser\Google;
use App\Components\Parser\Parser;

class DocumentProcessor
{
    /**
     * Document model
     * @var Document
     */
    protected $document;

    /**
     * Parser instance
     * @var Parser
     */
    protected $parser;

    /**
     * Import options
     * @var array
     */
    protected $options;

    /**
     * Processed data
     */
    protected $data;

    /**
     * Class constructor
     */
    public function __construct(Document $Document, array $options = [])
    {
        // Set attributes
        $this->document = $Document;
        $this->options = $options;

        try {
            $this->process();
        } catch (\Exception $e) {
            // @TODO Log message here
            throw $e;
        }
    }

    /**
     * Process
     */
    public function process(): void
    {
        // Parse document
        switch ($this->document->type) {
            case Document::TYPE_CSV:
                $this->parser = new Csv($this->document, $this->options);
                break;
            case Document::TYPE_XLSX:
                $this->parser = new Xls($this->document, $this->options);
                break;
            case Document::TYPE_GOOGLE:
                $this->parser = new Google($this->document, $this->options);
                break;
            default:
                throw new \Exception('DocumentProcessor::process - Unsupported document type of ' . $this->document->type);
        }
    }

    /**
     * Get results
     */
    public function results()
    {
        return $this->parser->process();
    }

    /**
     * Get total
     */
    public function total()
    {
        return $this->document->data_total;
    }

    /**
     * Get size
     */
    public function size()
    {
        return $this->document->data_size;
    }

    /**
     * Get first
     */
    public function first()
    {
        return $this->document->data_first;
    }

    /**
     * Get last
     */
     public function last()
    {
        return $this->document->data_last;
    }

    /**
     * Get start
     */
    public function start()
    {
        return $this->document->data_from;
    }

    /**
     * Get end
     */
    public function end()
    {
        return $this->document->data_to;
    }

    /**
     * Get count
     */
     public function count()
    {
        return $this->document->data_count;
    }

    /**
     * Get type
     */
    public function type()
    {
        return $this->document->type;
    }

    /**
     * Get options
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Get document
     */
    public function document()
    {
        return $this->document;
    }
}
