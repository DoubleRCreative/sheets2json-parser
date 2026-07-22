<?php

use Illuminate\Support\Facades\Schedule;

// Cleanup Expired Exports
Schedule::command('app:document-cleanup')->everyFiveMinutes()
    ->withoutOverlapping();
