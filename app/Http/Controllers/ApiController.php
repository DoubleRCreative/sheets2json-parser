<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $docsUrl = rtrim((string) config('app.url'), '/') . '/docs/';
        $apiRoot = rtrim(route('api.root'), '/');

        $payload = [
            'type' => 'api',
            'name' => config('app.name'),
            'status' => 'active',
            'description' => 'Parse and extract remote tabular document data to JSON.',
            'current_version' => 'v2',
            'urls' => [
                'root' => $apiRoot,
                'documentation' => $docsUrl,
                'openapi' => route('api.openapi'),
            ],
            'auth' => null,
            // 'endpoints' => [
            //     'v2' => route('api.document.data'),
            //     'v1' => [
            //         'url' => route('api.document.data.legacy'),
            //         'deprecated' => true,
            //     ],
            // ],
            'mcp' => [
                'description' => 'MCP Server to interact with Sheets2Json via AI agents over HTTP. Supports remote document parsing via Document Data API.',
                'endpoint' => route('api.mcp.document'),
                'type' => 'remote',
                'protocol' => 'HTTP'
            ]
        ];

        return response()->json($payload)->withHeaders([
            'Link' => '<' . $docsUrl . '>; rel="documentation", <' . route('api.openapi') . '>; rel="service-desc"',
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
