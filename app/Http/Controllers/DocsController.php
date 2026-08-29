<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    /**
     * Render the Scalar API reference page.
     */
    public function index(): View
    {
        return view('docs');
    }

    /**
     * Serve the OpenAPI specification document.
     */
    public function openapi(): Response
    {
        return response(
            file_get_contents(base_path('openapi.yml')),
            200,
            ['Content-Type' => 'application/x-yaml; charset=utf-8']
        );
    }
}