<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function tearDown(): void 
    {
        $this->artisan('migrate:reset');
        parent::tearDown();
    }
}
