<?php

namespace Tests\Feature\Controllers\v1;

use Tests\Feature\Controllers\TestController;

class DocumentStreamControllerTest extends TestController
{
    protected function getStreamedContent($response): string
    {
        ob_start();
        ob_start();
        $response->baseResponse->sendContent();
        ob_end_clean();
        return ob_get_clean() ?: '';
    }

    protected function parseStream(string $content): array
    {
        $lines = array_filter(explode("\n", trim($content)), fn($l) => $l !== '');
        $all = array_map(fn($l) => json_decode($l, true, 512, JSON_THROW_ON_ERROR), $lines);
        return [
            'rows' => array_slice($all, 0, -1),
            'result' => $all[count($all) - 1],
        ];
    }

    protected function googleUrl(): string
    {
        return 'https://docs.google.com/spreadsheets/d/15g64-KCC94m0PMlZJevaoUB84a71zIlVaVIhcD-Ey-4/edit';
    }

    public function test_stream_returns_ndjson_with_headers(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-ndjson');

        $parsed = $this->parseStream($this->getStreamedContent($response));

        // Rows
        $this->assertNotEmpty($parsed['rows']);
        foreach ($parsed['rows'] as $row) {
            $this->assertSame('row', $row['type']);
            $this->assertArrayHasKey('metadata', $row);
            $this->assertArrayHasKey('index', $row['metadata']);
            $this->assertArrayHasKey('size', $row['metadata']);
            $this->assertArrayHasKey('data', $row);
            $this->assertArrayHasKey('Row_1_Value_1', $row['data']);
        }

        // Result
        $this->assertSame('result', $parsed['result']['type']);
        $this->assertSame('complete', $parsed['result']['status']);
        $this->assertSame(count($parsed['rows']), $parsed['result']['count']);
        $this->assertGreaterThan(0, $parsed['result']['total']);
        $this->assertGreaterThan(0, $parsed['result']['size']);
        $this->assertNotEmpty($parsed['result']['document_url']);
        $this->assertNotEmpty($parsed['result']['document_type']);
        $this->assertNotEmpty($parsed['result']['document_id']);
        $this->assertNull($parsed['result']['error']);
    }

    public function test_stream_returns_ndjson_without_headers(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-ndjson');

        $parsed = $this->parseStream($this->getStreamedContent($response));

        $this->assertNotEmpty($parsed['rows']);
        foreach ($parsed['rows'] as $row) {
            $this->assertSame('row', $row['type']);
            $this->assertSame([0, 1, 2, 3, 4], array_keys($row['data']));
        }
        $this->assertSame('complete', $parsed['result']['status']);
    }

    public function test_stream_respects_column_limits(): void
    {
        $baseQuery = [
            'url' => $this->googleUrl(),
            'headers' => 1,
        ];

        $limitKeys = ['Row 1 Value 1', 'Row_1_Value_2'];
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query(array_merge($baseQuery, [
            'columns' => implode(',', $limitKeys),
        ])));

        $response->assertStatus(200);
        $parsed = $this->parseStream($this->getStreamedContent($response));

        $this->assertNotEmpty($parsed['rows']);
        foreach ($parsed['rows'] as $row) {
            $this->assertCount(2, $row['data']);
            $this->assertArrayHasKey('Row_1_Value_1', $row['data']);
            $this->assertArrayHasKey('Row_1_Value_2', $row['data']);
        }
        $this->assertSame('complete', $parsed['result']['status']);
    }

    public function test_stream_respects_range(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 1,
            'range' => '2,3',
        ]));

        $response->assertStatus(200);
        $parsed = $this->parseStream($this->getStreamedContent($response));

        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('complete', $parsed['result']['status']);
    }

    public function test_stream_respects_offset(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 1,
            'offset' => 1,
        ]));

        $response->assertStatus(200);
        $parsed = $this->parseStream($this->getStreamedContent($response));

        $this->assertNotEmpty($parsed['rows']);
        $this->assertSame('row', $parsed['rows'][0]['type']);
        $this->assertArrayHasKey('data', $parsed['rows'][0]);
    }

    public function test_stream_validates_required_url(): void
    {
        $response = $this->request()->get('/v1/doc/stream');

        $response->assertStatus(422);
    }

    public function test_stream_validates_invalid_range(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'range' => 'invalid',
        ]));

        $response->assertStatus(422);
    }

    public function test_stream_handles_invalid_url(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => 'not-a-valid-url',
        ]));

        $response->assertStatus(422);
    }

    public function test_stream_each_line_is_valid_json(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 1,
        ]));

        $response->assertStatus(200);
        $content = $this->getStreamedContent($response);

        $lines = array_filter(explode("\n", trim($content)), fn($l) => $l !== '');
        $this->assertNotEmpty($lines);
        foreach ($lines as $line) {
            $this->assertNotNull(json_decode($line, true), "Line is not valid JSON: $line");
        }
    }

    public function test_stream_includes_http_headers(): void
    {
        $response = $this->request()->get('/v1/doc/stream?' . http_build_query([
            'url' => $this->googleUrl(),
            'headers' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-ndjson');
        $response->assertHeader('X-Document-Hash');
        $response->assertHeader('X-Document-Type');
        $this->assertNotEmpty($response->headers->get('X-Document-Hash'));
        $this->assertNotEmpty($response->headers->get('X-Document-Type'));
    }
}
