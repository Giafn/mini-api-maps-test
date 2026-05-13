<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the Mini API! Please refer to the documentation at /docs for usage instructions.',
    ]);
});

// API documentation is provided by Scramble (https://scramble.dedoc.co/)
// After installing Scramble the UI will be available at /docs and the
// OpenAPI JSON at /docs/openapi.json (configurable in AppServiceProvider).
