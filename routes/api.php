<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;

// Company routes
Route::post('/company', [CompanyController::class, 'store']);
Route::get('/company/{edrpou}/versions', [CompanyController::class, 'versions']);

// Swagger JSON route (workaround)
Route::get('/docs', function () {
    return response()->file(storage_path('api-docs/api-docs.json'), [
        'Content-Type' => 'application/json'
    ]);
})->name('l5-swagger.default.docs');