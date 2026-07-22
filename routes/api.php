<?php

use Illuminate\Support\Facades\Route;

$apiDomains = config('domains.api', []);
$webDomains = config('domains.web', []);
$apiOnlyDomains = array_values(array_diff($apiDomains, $webDomains));
$sharedDomains = array_values(array_intersect($apiDomains, $webDomains));

foreach ($apiOnlyDomains as $domain) {
    Route::domain($domain)
        ->middleware(['api.domain'])
        ->group(base_path('routes/api-domain.php'));
}

foreach ($sharedDomains as $domain) {
    Route::domain($domain)
        ->prefix('api')
        ->middleware(['api.domain'])
        ->group(base_path('routes/api-domain.php'));
}
