<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect to API documentation
Route::get('/docs', function () {
    return redirect('/api/documentation');
});
