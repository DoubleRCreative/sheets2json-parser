<?php

namespace App\Components\Document;

use App\Components\Document\Document;
use App\Components\Http\Client;
use App\Components\Http\GoogleClient;
use App\Components\Http\HttpService;
use App\Components\Http\MicrosoftClient;
use Illuminate\Http\Client\Response;

class DocumentService extends HttpService
{
    /**
     * Max byte size
     */
    public const MAX_BYTE_SIZE = 50 * 1024 * 1024; // 50MB Hard limit

    /**
     * Stream chunk size
     */
    public const CHUNK_SIZE = 8192; // 8KB

    /**
     * Document data
     */
    protected $data;

    /**
     * Document type
     */
    protected $type;

    /**
     * Service options
     */
    protected $options;

    /**
     * Cache
     */
    protected $cache;

    /**
     * Class constructor
     */
    public function __construct(string $url = null, array $options = [], bool $cache = false)
    {
        $this->cache = $cache;
        $this->src = $url;
        $this->options = array_merge([
            Document::OPTION_SIZE => (1000 * 1024), // 1MB default
            Document::OPTION_LIMIT => 1000,
            'timeout' => 6 // 6 Second timeout
        ], array_filter($options));
    }

    /**
     * Get
     * Make request using set client
     * 
     * @return Document
     */
    public function get(): Document
    {
        // Set source url
        $url = $this->src . http_build_query([
            'sheet' => $this->options[Document::OPTION_TARGET] ?? null
        ]);
        // Hash url for unique ID
        $hash = hash('sha256', $url);
        // Attempt to find any existing document
        $document = Document::where('hash', $hash)->first();
        if ((!empty($document) && !$document->isExpired()) && file_exists($document->path) && $this->cache) {
            $document->options = $this->options;
            return $document;
        }
        
        // Get tempfile name
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR .  'doc_' . $hash;
        
        // Get remote content as stream
        $res = $this->client()->setOptions($this->options)->setHeaders(['cache-control' => 'no-cache'])->getStream();

        // Validate response status
        if ($res->status() !== 200) {
            throw new \Exception('Error trying to get remote file from source=' . $this->src, $res->status());
        }

        // Validate content length
        if (!empty($res->header('Content-Length')) && $res->header('Content-Length') > self::MAX_BYTE_SIZE) {
            throw new \Exception('Content too large from source=' . $this->src . ' max_size=' . self::MAX_BYTE_SIZE, 413);
        }

        // Set content types
        if (str_contains($res->header('Content-Type'), 'text/plain')) {
            $this->setType(Document::TYPE_CSV);
        }
        if (str_contains($res->header('Content-Type'), 'csv')) {
            $this->setType(Document::TYPE_CSV);
        }
        if (str_contains($res->header('Content-Type'), 'officedocument.spreadsheetml.sheet')) {
            $this->setType(Document::TYPE_XLSX);
        }
        if (str_contains($res->header('Content-Type'), 'application/octet-stream')) {
            $this->setType(Document::TYPE_STREAM);
        }

        // Validate content type 
        if (empty($this->type)) {
            throw new \Exception('Invalid document type from source=' . $this->src, 400);
        }

        // Save stream to temp file
        $this->writeStream($res->getBody(), $tempFile);

        // If document type is still stream, lets try to detect the proper mime type
        // from the data written into the tmpfile
        if ($this->type === Document::TYPE_STREAM) {
            $type = $this->detect_tmpfile_type($tempFile);
            // If we detect a new type from the data directly, update it here
            if (!empty($type)) {
                $this->setType($type);
            }
        }

        // Create new document if none exists
        if (empty($document)) {
            $document = Document::create([
                'hash' => $hash,
                'path' => $tempFile,
                'type' => $this->type,
                'source' => $url,
                'expires_at' => now()->addMinutes(5)
            ]);
        } else {
            $document->update(['expires_at' => now()->addMinutes(5)]);
        }

        // Set current options for document
        $document->options = $this->options;

        // Return document
        return $document;
    }

    /**
     * Set source
     * Alternate way to set source outside of constructor
     * Chain with additional methods, if needed
     */
    public function setUrl(string $url)
    {
        $this->src = $url;
        return $this;
    }

    /**
     * Set options
     * Add/change options after class init
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Client
     * Determine the appropriate Http client to use
     * 
     * @return Client
     */
    protected function client(): Client
    {
        // Parse url
        $src = parse_url($this->src);
        // Google sheets
        if (str_contains($src['host'], 'docs.google.com')) {
            $this->client = new GoogleClient($this->src);
            $this->setType(Document::TYPE_GOOGLE);
        }
        // Microsoft sharepoint / One Drive
        elseif (str_contains($src['host'], 'my.sharepoint.com') || str_contains($src['host'], '1drv.ms') || str_contains($src['host'], 'live.com')) {
            $this->client = new MicrosoftClient($this->src);
            $this->setType(Document::TYPE_MICROSOFT);
        }
        // Default
        else {
            $this->client = new Client($this->src);
        }
        // Return client
        return $this->client;
    }

    /**
     * Set type
     * Helper method to set the returned document type
     * 
     * @param string $type
     * @return void
     */
    protected function setType(string $type): void
    {
        if (!in_array($type, Document::getTypes())) {
            throw new \Exception('DocumentService::type not supported');
        }
        $this->type = $type;
    }

    /**
     * Write stream to file
     */
    protected function writeStream($stream, $tempFile)
    {
        $totalBytes = 0;

        $outputStream = fopen($tempFile, 'w+');

        while (!$stream->eof()) {
            $chunk = $stream->read(self::CHUNK_SIZE);
            $bytes = strlen($chunk);

            $totalBytes += $bytes;

            if ($totalBytes > self::MAX_BYTE_SIZE) {
                fclose($outputStream);
                unlink($tempFile); // cleanup partial file

                throw new \Exception("Content too large from source={$this->src}", 413);
            }

            fwrite($outputStream, $chunk);
        }

        fclose($outputStream);
    }

    /**
     * Detect file type from path
     */
    private function detect_tmpfile_type(string $filePath): string
    {
        if (!is_readable($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        // XLSX files are ZIP archives ("PK\x03\x04")
        if (fread($handle, 4) === "PK\x03\x04") {
            fclose($handle);
            return Document::TYPE_XLSX;
        }

        // Rewind and see if the first line parses as CSV.
        rewind($handle);

        $row = fgetcsv($handle);

        fclose($handle);

        return is_array($row)
            ? Document::TYPE_CSV
            : false;
    }
}
