<?php

use App\Http\Controllers\Api\CollectController;
use Illuminate\Support\Facades\Route;

// Public, unauthenticated, CORS-open ingestion endpoints hit by the
// tracking snippet embedded on customer sites. No auth is possible here —
// the site's `domain` field plus a rate limiter is the only gate.
Route::middleware('throttle:analytics-ingest')->group(function () {
    Route::post('/collect', [CollectController::class, 'store']);
    Route::post('/collect/duration', [CollectController::class, 'duration']);
});
