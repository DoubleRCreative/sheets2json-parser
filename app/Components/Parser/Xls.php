<?php

namespace App\Components\Parser;

use App\Components\Document\Document;
use Exception;
use Generator;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;

/**
 * CSV file class
 * This class handles the specific logic for working with CSV (comma separated values) files and their data
 */
class Xls extends Parser
{
    /**
     * Parser type
     * @var string
     */
    protected $type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

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
            // $this->tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
            // $outputStream = fopen($this->tmpFile, 'w+');
            // stream_copy_to_stream($stream->detach(), $outputStream);
            // fclose($outputStream);

            // Set options
            $options = new \OpenSpout\Reader\XLSX\Options();
            $options->SHOULD_PRESERVE_EMPTY_ROWS = true;

            // Set reader, and open data source
            $this->reader = new \OpenSpout\Reader\XLSX\Reader($options);
            $this->reader->open($this->document->path);

            // Set target sheet
            $targetSheet = $this->document->options[Document::OPTION_TARGET] ?? null;
            $sheetFound = false;
            foreach ($this->reader->getSheetIterator() as $sheet) {
                if (!empty($targetSheet)) {
                    if ($sheet->getName() === $targetSheet) {
                        // Get rows for sheet
                        $this->staged = $sheet->getRowIterator();
                        $sheetFound = true;
                        break;
                    }
                } else {
                    // Get rows for sheet
                    $this->staged = $sheet->getRowIterator();
                    break;
                }
            }

            // Throw exception if target sheet was specified but not found
            if (!empty($targetSheet) && !$sheetFound) {
                throw new InvalidArgumentException('Target sheet not found in document', 400); // Bubble up http code
            }

        } catch (\Exception $e) {
            // @TODO log message here
            $this->cleanup();
            throw $e;
        }

        // Return data
        return $this->results();
    }

    /**
     * Clean up
     */
    public function cleanup(): void
    {
        $this->reader->close();
        //unlink($this->tmpFile);
    }

    /**
     * Transform row
     */
    public function transformRow(mixed $row): array
    {
        if (!$row instanceof \OpenSpout\Common\Entity\Row) {
            return $row;
        }

        $cells = $row->getCells();
        $rowArr = [];
        foreach ($cells as $cell) {
            if ($cell instanceof FormulaCell) {
                // Compute formula of cell, fallback to un-computed output
                $value = $cell->getComputedValue() ?? $cell->getValue();
            } else {
                $value = $cell->getValue();
            }
            $rowArr[] = is_string($value) ? trim($value) : (string) trim($value);
        }
        return $rowArr;
    }
}
