<?php

namespace Tests\Feature\Controllers;

class DocsControllerTest extends TestController
{
    public function test_web_route_renders_scalar_api_reference(): void
    {
        $response = $this->request()->get('/docs');
        $response->assertStatus(200);
        $response->assertSee('@scalar/api-reference', false);
        $response->assertSee('data-url="/docs/openapi.yml"', false);
    }

    public function test_openapi_spec_is_served(): void
    {
        $response = $this->request()->get('/docs/openapi.yml');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-yaml; charset=utf-8');
        $response->assertSee('openapi:', false);
        $response->assertSee('/doc/stream', false);
    }
}