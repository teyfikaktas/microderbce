<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json(['message' => 'API working!']);
});

Route::middleware('api')->group(function () {
    // Test route
    Route::get('/jobs', function () {
        return response()->json(['jobs' => []]);
    });
});