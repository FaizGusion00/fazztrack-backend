<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'FazzTrack API Server',
        'status' => 'running',
        'api_endpoint' => url('/api'),
        'health_check' => url('/api/health'),
        'documentation' => 'Visit /api/health for API status'
    ]);
});
