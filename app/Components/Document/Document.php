<?php

namespace App\Components\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * Document model
 * This is a model facade to mock out a model object within laravel,
 * it is not persisted to the database. It represents a physical document from
 * a remote location (url)
 * 
 * It contains constant variables used throughout the application to standardize
 * and make management of remote documents easier.
 */
class Document extends Model
{
    /**
     * Database table (none)
     */
    protected $table = 'documents';

    /**
     * Total number of rows
     * @var int
     */
    protected $data_total = 0;

    /**
     * Size of all processed rows (bytes)
     * @var int
     */
    protected $data_size = 0;

    /**
     * Number of rows processed
     * @var int
     */
    protected $data_count = 0;

    /**
     * First row processed
     * @var int
     */
    protected $data_first;

    /**
     * Last row processed
     * @var int
     */
    protected $data_last;

    /**
     * Errors
     * @var DocumentException
     */
    protected $error;

    /**
     * Document types
     * MIME_types for supported document formats
     */
    public const TYPE_CSV = 'text/csv';
    public const TYPE_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const TYPE_GOOGLE = 'application/vnd.google-apps.spreadsheet';
    public const TYPE_MICROSOFT = self::TYPE_XLSX;
    public const TYPE_STREAM = 'application/octet-stream'; // Generic binary stream (will use additional detection methods)

    /**
     * Document Options
     */
    public const OPTION_HEADERS = 'document_headers';
    public const OPTION_TARGET = 'document_target';
    public const OPTION_TYPE = 'document_type';
    public const OPTION_URL = 'document_url';
    public const OPTION_SIZE = 'document_max_size'; //max_res_size
    public const OPTION_LIMIT = 'document_record_limit'; //doc_record_limit
    public const OPTION_RANGE = 'document_range';
    public const OPTION_COLUMNS = 'document_columns';
    public const OPTION_SKIP_EMPTY = 'document_skip_empty'; // Skip empty/blank rows
    public const OPTION_OFFSET = 'document_offset';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source',
        'type',
        'options',
        'data',
        'path',
        'hash',
        'expires_at',
        'data_count',
        'data_total',
        'data_first',
        'data_last',
        'data_size'
    ];

    /**
     * Validation rules
     * 
     * @var array
     */
    public $rules = [
        'hash' => 'required|string',
        'type' => 'required|string',
        'source' => 'required|string',
        'path' => 'required|string',
        'expires_at' => 'required|date',
        'options' => 'array',
        //'data' => 'required',
        'data_count' => 'integer',
        'data_total'  => 'integer',
        'data_first' => 'integer',
        'data_last' => 'integer',
        'data_size' => 'integer'
    ];

    /**
     * Get document types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CSV,
            self::TYPE_XLSX,
            self::TYPE_GOOGLE,
            self::TYPE_MICROSOFT,
            self::TYPE_STREAM
        ];
    }

    /**
     * Get document options
     */
    public static function getOptions(): array
    {
        return [
            self::OPTION_URL,
            self::OPTION_TYPE,
            self::OPTION_TARGET,
            self::OPTION_HEADERS,
            self::OPTION_RANGE,
            self::OPTION_COLUMNS
        ];
    }

    /**
     * Validate model data
     * Catch exceptions and return bool instead
     */
    public function validate(): bool
    {
        try {
            Validator::make($this->toArray(), $this->rules)->validate();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public static function getRange(?string $range)
    {
        if (empty($range)) return [];
        $arr = explode(',', trim($range));
        if (count($arr) < 2) return [];
        return [(int)$arr[0], (int)$arr[1]];
    }

    public function isExpired(): bool
    {
        return ($this->expires_at <= now());
    }
}