<?php

use App\Mcp\Servers\Sheets2JsonServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('sheets2json', Sheets2JsonServer::class);

Mcp::web('/mcp/document', Sheets2JsonServer::class)
    ->middleware(['api.domain', 'throttle:data-api-document'])
    ->name('api.mcp.document');
