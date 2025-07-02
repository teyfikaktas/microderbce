<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\JobSearchController;
use App\Http\Controllers\API\SearchAnalyticsController;
use Illuminate\Support\Facades\Http; // Bu satırı ekleyin

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json(['message' => 'API working!']);
});

Route::middleware('api')->group(function () {
    
    // Job Search routes
    Route::prefix('v1/jobs')->group(function () {
        Route::get('/', [JobSearchController::class, 'jobs']);
        Route::get('/{id}', [JobSearchController::class, 'getJob']);
        Route::get('/{id}/related', [JobSearchController::class, 'getRelatedJobs']);
        Route::post('/search', [JobSearchController::class, 'search']);
    });
// routes/api.php (ana site)

// Session-based AI routes

    // Search Analytics routes (MongoDB)
    Route::prefix('v1/search-analytics')->group(function () {
        // Arama kaydet
        Route::post('/save', [SearchAnalyticsController::class, 'saveSearch']);
        
        // Son aramaları getir
        Route::get('/recent/{userId?}', [SearchAnalyticsController::class, 'getRecentSearches']);
        
        // Popüler aramaları getir
        Route::get('/popular', [SearchAnalyticsController::class, 'getPopularSearches']);
        
        // Kullanıcı davranış analizi
        Route::get('/behavior/{userId}', [SearchAnalyticsController::class, 'getUserBehavior']);
        
        // AI Agent için kullanıcı profili
        Route::get('/ai-profile/{userId}', [SearchAnalyticsController::class, 'getAIUserProfile']);
    });

    // Backward compatibility için eski endpoint'ler
    Route::get('/v1/recent-searches/{userId?}', [SearchAnalyticsController::class, 'getRecentSearches']);
    Route::post('/v1/recent-searches', [SearchAnalyticsController::class, 'saveSearch']);

    // Autocomplete endpoints
    Route::prefix('v1/autocomplete')->group(function () {
        Route::get('/positions', [JobSearchController::class, 'getPositionSuggestions']);
        Route::get('/cities', [JobSearchController::class, 'getCitySuggestions']);
    });

    // Health check
    Route::get('/v1/health', [JobSearchController::class, 'health']);
});