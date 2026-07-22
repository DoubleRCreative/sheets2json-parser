<?php

namespace App\Components\Document;

use Exception;

class DocumentException extends Exception
{
    /**
     * Exception types
     */
    protected $type;

    /**
     * Document
     * @var Document
     */
    protected $document;
    
    /**
     * Data
     * @var array
     */
    protected $error = [];

    /**
     * Error code
     * @var int
     */
    protected $code = 0;

    /**
     * Defined exception types
     */
    public const DATA_LIMIT = 'data_limit';
    public const DATA_SIZE = 'data_size';

    public function __construct(string $type, Document $document)
    {
        // Set document  
        $this->document = $document;
        // Set type
        $this->setType($type);
        // Set attributes based on type
        switch($this->type) {
            case self::DATA_LIMIT:
                $this->dataLimitType();
                break;
            case self::DATA_SIZE:
                $this->dataSizeType();
                break;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    protected function setType(string $type): void
    {
        if (!in_array($type, [
            self::DATA_LIMIT,
            self::DATA_SIZE
        ])) {
            throw new \Exception('DocumentException::setType - Invalid type');
        }
        $this->type = $type;
    }

    protected function dataLimitType(): void
    {
        $this->message = "Maximum record limit exceeded";
        $this->code = 0;
        $this->error = [
            'type' => self::DATA_LIMIT,
            'message' => $this->message,
            'limit' => $this->document->options[Document::OPTION_LIMIT]
        ];
    }

    protected function dataSizeType(): void
    {
        $this->message = "Maximum data size exceeded";
        $this->code = 0;
        $this->error = [
            'type' => self::DATA_SIZE,
            'message' => $this->message,
            'limit' => $this->document->options[Document::OPTION_SIZE]
        ];
    }
}
