<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\DocumentController;
use App\Http\Requests\DocumentRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('document.parse')]
#[Description('Fetch and parse a public CSV/XLSX/Google Sheets URL using the Sheets2JSON document API endpoint.')]
#[IsReadOnly]
#[IsIdempotent]
class DocumentParseTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $input = array_filter($request->all(), static fn($value) => $value !== null && $value !== '');

        try {
            $documentRequest = DocumentRequest::create(
                '/v1/doc/stream',
                'GET',
                $input
            );

            $documentRequest->setContainer(app());
            $documentRequest->setRedirector(app('redirect'));
            $documentRequest->validateResolved();

            $response = app(DocumentController::class)->indexV2($documentRequest);
        } catch (\Throwable $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::structured($response);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()
                ->description('Public CSV/XLSX/Google Sheets/Microsoft Excel Online URL to parse.')
                ->required(),
            'headers' => $schema->boolean()
                ->description('Set true when first row contains header names.')
                ->default(false),
            'sheet' => $schema->string()
                ->description('Optional sheet name or target for multi-sheet documents.'),
            'range' => $schema->string()
                ->description('Optional range expression supported by the document endpoint, can be used as pagination. Format is row index start,end (1,100)'),
            'columns' => $schema->string()
                ->description('Optional comma-separated column indexes or names.'),
            'skip_empty' => $schema->boolean()
                ->description('Set true to skip empty rows that do not contain any value in any column (empty/blank row).')
                ->default(false),
            'offset' => $schema->integer()
                ->description('Optional row offset. Skip rows before this index (1-based). 0 means no offset.')
                ->default(0),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, JsonSchema>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()
                ->description('Parsed document rows returned as objects or arrays depending on parser/header mode.')
                ->required(),
            'type' => $schema->string()
                ->description('Resource type identifier returned by the API.')
                ->required(),
            'id' => $schema->string()
                ->description('Stable identifier for this document response (document hash).')
                ->required(),
            'attributes' => $schema->object([
                'document_url' => $schema->string()
                    ->description('Source document URL that was parsed.')
                    ->required(),
                'document_type' => $schema->string()
                    ->description('Detected document MIME/content type.')
                    ->required(),
                'document_headers' => $schema->boolean()
                    ->description('Whether the first row was treated as column headers.')
                    ->required(),
                'document_target' => $schema->string()
                    ->description('Optional sheet/target selector used during parsing.')
                    ->nullable(),
                'data_count' => $schema->integer()
                    ->description('Number of rows returned in this response.')
                    ->required(),
                'data_from' => $schema->integer()
                    ->description('Start position of returned rows (1-based).')
                    ->required(),
                'data_to' => $schema->integer()
                    ->description('End position of returned rows (1-based).')
                    ->required(),
                'data_next' => $schema->integer()
                    ->description('Next starting position for follow-up pagination requests, if available.')
                    ->nullable(),
                'data_first' => $schema->integer()
                    ->description('Index of the first row in the full source dataset (1-based).')
                    ->required(),
                'data_last' => $schema->integer()
                    ->description('Index of the last row in the full source dataset (1-based).')
                    ->required(),
                'data_total' => $schema->integer()
                    ->description('Total rows available in the parsed dataset.')
                    ->required(),
                'data_size' => $schema->integer()
                    ->description('Approximate payload size in bytes for returned rows.')
                    ->required(),
                'created_at' => $schema->string()
                    ->description('Timestamp when this response was generated (server local time).')
                    ->required(),
            ])->description('Document-level metadata and pagination attributes.')
                ->required(),
            'metadata' => $schema->object()
                ->description('Optional metadata such as non-fatal parsing warnings or errors.')
                ->nullable(),
        ];
    }
}
