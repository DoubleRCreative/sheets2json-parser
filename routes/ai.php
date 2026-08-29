<?php

use App\Mcp\Servers\Sheets2JsonServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('sheets2json', Sheets2JsonServer::class);

Mcp::web('/mcp/document', Sheets2JsonServer::class)->name('api.mcp.document');
