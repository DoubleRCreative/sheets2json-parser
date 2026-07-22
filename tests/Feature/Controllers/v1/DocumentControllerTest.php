<?php

namespace Tests\Feature\Controllers\v1;

use Tests\Feature\Controllers\TestController;

class DocumentControllerTest extends TestController
{
    public function test_user_can_get_document_data(): void
    {
        $response = $this->request()->get('/api/v2/doc?' . http_build_query([
            'url' => 'https://docs.google.com/spreadsheets/d/15g64-KCC94m0PMlZJevaoUB84a71zIlVaVIhcD-Ey-4/edit',
            'headers' => 1,
        ]) );
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotNull($data['data']);
    }

    public function test_user_can_get_document_data_legacy(): void
    {
        $response = $this->request()->get('/api/v1/doc?' . http_build_query([
            'url' => 'https://docs.google.com/spreadsheets/d/15g64-KCC94m0PMlZJevaoUB84a71zIlVaVIhcD-Ey-4/edit',
            'headers' => 1,
        ]) );
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(3, $data);
    }

    public function test_user_can_limit_document_columns(): void
    {
        $baseQuery = [
            'url' => 'https://docs.google.com/spreadsheets/d/15g64-KCC94m0PMlZJevaoUB84a71zIlVaVIhcD-Ey-4/edit',
            'headers' => 1,
        ];

        $limitKeys = ['Row 1 Value 1', 'Row_1_Value_2'];
        $limitedResponse = $this->request()->get('/api/v2/doc?' . http_build_query(array_merge($baseQuery, [
            'columns' => implode(',', $limitKeys),
        ])));
        $limitedResponse->assertStatus(200);
        $limitedData = $limitedResponse->json();
        $this->assertNotEmpty($limitedData);
        $this->assertCount(2, $limitedData['data'][0]);
    }
}
