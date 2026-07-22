<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Components\Document\Document;
use App\Components\Utility\Data;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Components\Document\DocumentService;
use App\Components\Document\DocumentException;
use App\Components\Document\DocumentProcessor;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStreamController extends Controller
{

    public function stream(DocumentRequest $Request): StreamedResponse
    {
        try {
            $request = $Request->validated();

            $url = $request['url'];
            $headers = $request['headers'] ?? 0;
            $sheet = $request['sheet'] ?? null;
            $range = $request['range'] ?? null;
            $columns = $request['columns'] ?? null;
            $skipEmpty = $request['skip_empty'] ?? false;
            $offset = $request['offset'] ?? 0;

            $documentOptions = [
                Document::OPTION_SIZE => 10000000,
                Document::OPTION_LIMIT => 10000000,
                Document::OPTION_HEADERS => $headers,
                Document::OPTION_TARGET => $sheet,
                Document::OPTION_RANGE => $range,
                Document::OPTION_COLUMNS => $columns,
                Document::OPTION_SKIP_EMPTY => $skipEmpty,
                Document::OPTION_OFFSET => $offset,
            ];

            $document = new DocumentService(url: $url, options: $documentOptions, cache: true);
            $document = $document->get();
            if (empty($document->validate())) {
                abort(400, 'Content is invalid');
            }

            $processor = new DocumentProcessor($document, $documentOptions);

            return new StreamedResponse(function () use ($processor, $document, $request) {
                // Data rows
                $index = 0;
                foreach ($processor->results() as $item) {
                    $index++;
                    $row = [];
                    $data = Data::toArrayRecursive($item);
                    $row['type'] = 'row';
                    $row['metadata'] = [
                        'index' => $data['__index'] ?? null,
                        'size' => $data['__size'] ?? null
                    ];
                    unset($data['__index'], $data['__size']);
                    $row['data'] = $data;
                    echo json_encode($row) . "\n";

                    if (ob_get_level()) ob_flush();
                    flush();
                }

                // Lead-out
                $doc = $processor->document();
                $error = $doc->error ?? null;
                echo json_encode([
                    'type' => 'result',
                    'document_url' => $request['url'],
                    'document_type' => $document->type,
                    'document_id' => $document->hash,
                    'status' => $error ? 'truncated' : 'complete',
                    'count' => $processor->count(),
                    'total' => $processor->total(),
                    'first' => $processor->first(),
                    'last' => $processor->last(),
                    'from' => $processor->start(),
                    'to' => $processor->end(),
                    'size' => $processor->size(),
                    'error' => $error ? $error->getError() : null,
                ]);
                if (ob_get_level()) ob_flush();
                flush();
            }, 200, [
                'Content-Type' => 'application/x-ndjson',
                'X-Accel-Buffering' => 'no',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Document-Hash' => $document->hash,
                'X-Document-Type' => $document->type,
            ]);
        } catch (DocumentException $e) {
            abort(400, $e->getError());
        } catch (\Exception $e) {
            Log::error('DocumentStreamController::stream exception', [
                'type' => get_class($e),
                'msg' => $e->getMessage(),
                'url' => $url ?? $request['url'] ?? 'unknown',
            ]);

            $code = $e->getCode() !== 0 ? $e->getCode() : 500;
            $msg = $code === 500
                ? 'An unexpected error occurred, unable to parse document source.'
                : $e->getMessage();

            abort($code, $msg);
        }
    }

}
