<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;

/**
 * All tests using this class are using data from the 
 * Tests\Data\Database\DatabaseSeeder::class - most 
 * models will be using the ID of 1000000000
 */
class TestController extends TestCase
{
    /**
     * Attributes
     */
    protected $headers = [
        'Accept' => 'application/json'
    ];

    /**
     * Base request for controllers, removes JSON header checks
     */
    public function request()
    {
        return $this;
    }

}
