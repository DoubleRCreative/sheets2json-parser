<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Reserved request parameters
     * @var array
     */
    protected $reserved = [
        'sort',
        'sortAs',
        'page',
        'limit',
        'fields',
        'q'
    ];
}
