<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'service' => 'Job AI API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment()
    ]);
});

// AI Chat endpoints
Route::prefix('chat')->group(function () {
    
    // Main chat endpoint
    Route::post('/', [AIChatController::class, 'chat'])
        ->name('ai.chat');
    
    // Send message (alias for main chat)
    Route::post('/message', [AIChatController::class, 'chat'])
        ->name('ai.message');
    
    // Get chat history
    Route::get('/history/{userId?}', [AIChatController::class, 'getHistory'])
        ->name('ai.history');
    
    // Clear chat context
    Route::post('/clear-context', [AIChatController::class, 'clearContext'])
        ->name('ai.clear.context');
    
    // Get user context/profile
    Route::get('/profile/{userId}', [AIChatController::class, 'getUserProfile'])
        ->name('ai.user.profile');
});

// User analytics endpoints
Route::prefix('analytics')->group(function () {
    
    // Get user behavior analysis
    Route::get('/user/{userId}/behavior', [AIChatController::class, 'getUserBehavior'])
        ->name('ai.user.behavior');
    
    // Get user's recent searches
    Route::get('/user/{userId}/recent-searches', [AIChatController::class, 'getRecentSearches'])
        ->name('ai.user.recent.searches');
    
    // Get personalized recommendations
    Route::get('/user/{userId}/recommendations', [AIChatController::class, 'getRecommendations'])
        ->name('ai.user.recommendations');
    
    // Get similar users
    Route::get('/user/{userId}/similar', [AIChatController::class, 'getSimilarUsers'])
        ->name('ai.user.similar');
    
    // Get popular searches
    Route::get('/popular-searches', [AIChatController::class, 'getPopularSearches'])
        ->name('ai.popular.searches');
    
    // Get search statistics
    Route::get('/statistics/{userId?}', [AIChatController::class, 'getStatistics'])
        ->name('ai.statistics');
});

// AI Agent management endpoints
Route::prefix('agent')->group(function () {
    
    // Test Gemini AI connection
    Route::get('/test-gemini', [AIChatController::class, 'testGemini'])
        ->name('ai.test.gemini');
    
    // Test Supabase connection
    Route::get('/test-supabase', [AIChatController::class, 'testSupabase'])
        ->name('ai.test.supabase');
    
    // Test main site connection
    Route::get('/test-main-site', [AIChatController::class, 'testMainSite'])
        ->name('ai.test.main.site');
    
    // Get AI agent status
    Route::get('/status', [AIChatController::class, 'getAgentStatus'])
        ->name('ai.agent.status');
    
    // Reset AI agent context
    Route::post('/reset', [AIChatController::class, 'resetAgent'])
        ->name('ai.agent.reset');
});

// Debug endpoints (only in development)
if (app()->environment(['local', 'development', 'staging'])) {
    
    Route::prefix('debug')->group(function () {
        
        // Debug user context
        Route::get('/context/{userId}', [AIChatController::class, 'debugUserContext'])
            ->name('ai.debug.context');
        
        // Debug intent analysis
        Route::post('/intent', [AIChatController::class, 'debugIntent'])
            ->name('ai.debug.intent');
        
        // Debug Gemini prompt
        Route::post('/gemini-prompt', [AIChatController::class, 'debugGeminiPrompt'])
            ->name('ai.debug.gemini');
        
        // Get all environment variables (masked)
        Route::get('/env', function () {
            return response()->json([
                'GEMINI_API_KEY' => env('GEMINI_API_KEY') ? 'SET (' . substr(env('GEMINI_API_KEY'), 0, 10) . '...)' : 'NOT_SET',
                'SUPABASE_URL' => env('SUPABASE_URL') ?: 'NOT_SET',
                'SUPABASE_ANON_KEY' => env('SUPABASE_ANON_KEY') ? 'SET (' . substr(env('SUPABASE_ANON_KEY'), 0, 10) . '...)' : 'NOT_SET',
                'MAIN_SITE_URL' => env('MAIN_SITE_URL') ?: 'NOT_SET',
                'APP_ENV' => app()->environment(),
                'APP_DEBUG' => config('app.debug')
            ]);
        })->name('ai.debug.env');
    });
}

// Webhooks (for external integrations)
Route::prefix('webhooks')->group(function () {
    
    // Supabase webhook for real-time updates
    Route::post('/supabase', [AIChatController::class, 'handleSupabaseWebhook'])
        ->name('ai.webhook.supabase');
    
    // Main site webhook for job updates
    Route::post('/job-updates', [AIChatController::class, 'handleJobUpdates'])
        ->name('ai.webhook.jobs');
});

// CORS preflight for all routes
Route::options('{any}', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'POST /api/chat' => 'Main chat endpoint',
            'GET /api/chat/history/{userId}' => 'Get chat history',
            'GET /api/analytics/user/{userId}/behavior' => 'User behavior analysis',
            'GET /api/analytics/popular-searches' => 'Popular searches',
            'GET /api/agent/status' => 'AI agent status',
            'GET /api/health' => 'Health check'
        ],
        'documentation' => 'https://ai-api.213-238-168-122.plesk.page/api/health'
    ], 404);
});