<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\DocumentParseTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Sheets2JSON')]
#[Version('1.0.0')]
#[Instructions('Use this server to parse public spreadsheet and tabular data URLs into structured JSON data. Start with document parsing tools; collection tools will be added in future versions.')]
class Sheets2JsonServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        DocumentParseTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
