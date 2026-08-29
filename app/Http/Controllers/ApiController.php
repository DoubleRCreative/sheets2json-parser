<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payload = [
            'type' => 'api',
            'name' => config('app.name') . ' Parser',
            'status' => 'active',
            'description' => 'Parse and extract remote tabular document data to JSON.',
            'current_version' => 'v1',
            'urls' => [
                'root' => config('app.url'),
                'documentation' => route('docs'),
                'openapi' => route('docs.openapi'),
            ],
            'auth' => null,
            'endpoints' => [
                'json' => route('api.document'),
                'stream' => route('api.document.stream'),
            ],
            'mcp' => [
                'description' => 'MCP Server to interact with Sheets2Json via AI agents over HTTP. Supports remote document parsing via Document Data API.',
                'endpoint' => route('api.mcp.document'),
                'type' => 'remote',
                'protocol' => 'HTTP'
            ]
        ];

        return response()->json($payload)->withHeaders([
            'Link' => '<' . route('docs') . '>; rel="documentation", <' . route('api.openapi') . '>; rel="service-desc"',
        ]);
    }

    public function openapi(Request $request): JsonResponse
    {
        $openapiUrl = (string) config('services.api.openapi_url');
        if ($openapiUrl === '') {
            $openapiUrl = rtrim((string) config('app.url'), '/') . '/docs/openapi.yml';
        }

        return response()->json([
            'openapi_url' => $openapiUrl,
        ], 302, [
            'Location' => $openapiUrl,
        ]);
    }
}
