<?php

namespace App\Http\Controllers\Api;

use App\Components\Document\Document;
use App\Components\Utility\Data;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Components\Document\DocumentService;
use App\Components\Document\DocumentException;
use App\Components\Document\DocumentProcessor;

class DocumentController extends Controller
{

    /**
     * Response
     * @var array
     */
    protected $response;

    /**
     * V1 Route
     */
    public function index(DocumentRequest $Request)
    {
        try {
            // Validate request
            $request = $Request->validated();

            // Get request params
            $url = $request['url'];
            $headers = $request['headers'] ?? 0; // Default no headers
            $sheet = $request['sheet'] ?? null;
            $sort = $request['sort'] ?? '';
            $range = $request['range'] ?? null;
            $columns = $request['columns'] ?? null;
            $skipEmpty = $request['skip_empty'] ?? false;
            $offset = $request['offset'] ?? 0; // Default no offset (start at first row)
            $stream = $request['stream'] ?? 0; // Return a streamed response

            // Build document options
            $documentOptions = [
                Document::OPTION_SIZE => $Request->getSizeLimit(),
                Document::OPTION_LIMIT => $Request->getRecordLimit(),
                Document::OPTION_HEADERS => $headers,
                Document::OPTION_TARGET => $sheet,
                Document::OPTION_RANGE => $range,
                Document::OPTION_COLUMNS => $columns,
                Document::OPTION_SKIP_EMPTY => $skipEmpty,
                Document::OPTION_OFFSET => $offset
            ];

            // Result array placeholder
            $results = [];
        
            $document = new DocumentService(url: $url, options: $documentOptions, cache: true);
            $document = $document->get();
            if (empty($document->validate())) {
                abort(400, 'Content is invalid');
            }

            // Process document
            $processor = new DocumentProcessor($document, $documentOptions);
            foreach ($processor->results() as $item) {
                $results[] = Data::toArrayRecursive($item['data'] ?? []);
            }

            // Handle any non fatal error
            if (!empty($document->error)) {
                $this->response['metadata']['error'] = $document->error->getError();
            }
        } 
        // Subscription error
        catch (DocumentException $e) {
            // @TODO: Log error if needed
            $this->response['metadata']['error'] = $e->getError();
        }
        // Default error
        catch (\Exception $e) {
            Log::error('DocumentController::index exception', [
                'type' => get_class($e),
                'msg' => $e->getMessage(),
                'url' => $document->source ?? $url
            ]);

            if ($e->getCode() !== 0) {
                $code = $e->getCode();
                $msg = $e->getMessage();
            } else {
                $code = 500;
                $msg = 'An unexpected error occurred, unable to parse document source.';
            }

            abort($code, $msg);
        }

        return response()->json([
            'data' => $results ?? [],
            'type' => 'document',
            'id' => $document->hash,
            'attributes' => [
                'document_url' => $request['url'],
                'document_type' => $document->type,
                'document_headers' => $request['headers'] ?? 0,
                'document_target' => $request['sheet'] ?? null,
                'document_skip_empty' => $request['skip_empty'] ?? 0,
                'document_columns' => $request['columns'] ?? null,
                'data_count' => $processor->count(),
                'data_from' => $processor->start(),
                'data_to' => $processor->end(),
                'data_next' => $processor->next() ?? null,
                'data_first' => $processor->first(),
                'data_last' => $processor->last(),
                'data_total' => $processor->total(),
                'data_size' => $processor->size(),
                'created_at' => date('Y-m-d H:i:s')
            ],
            'metadata' => $this->response['metadata'] ?? null
        ]);
    }
}
