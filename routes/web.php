<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HomePage;
use App\Livewire\JobDetail;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\JobController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::prefix('admin')
    ->middleware(function ($request, $next) {
        if ($request->session()->get('user_email') !== 'admin@admin.com') {
            // Yetkili değilse login sayfasına yönlendir
            return redirect()->route('login');
        }
        return $next($request);
    })
    ->name('admin.')
    ->group(function() {

        // Listeleme
        Route::get('jobs', [JobController::class, 'index'])
            ->name('jobs.index');

        // Yeni iş formu
        Route::get('jobs/create', [JobController::class, 'create'])
            ->name('jobs.create');

        // Kaydet
        Route::post('jobs', [JobController::class, 'store'])
            ->name('jobs.store');

        // Düzenleme formu
        Route::get('jobs/{id}/edit', [JobController::class, 'edit'])
            ->name('jobs.edit');

        // Güncelle
        Route::put('jobs/{id}', [JobController::class, 'update'])
            ->name('jobs.update');

        // Sil
        Route::delete('jobs/{id}', [JobController::class, 'destroy'])
            ->name('jobs.destroy');
    });
Route::get('/', HomePage::class);
Route::get('/jobs/{id}', JobDetail::class)->name('job.detail');
// routes/web.php
Route::get('/login', App\Livewire\Auth\Login::class)->name('login');
Route::get('/register', App\Livewire\Auth\Register::class)->name('register');
Route::get('/company-register', App\Livewire\Auth\CompanyRegister::class)->name('company.register');
Route::match(['GET', 'POST'], '/logout', function() {
    auth()->logout();
    return redirect('/');
})->name('logout');
// routes/web.php
// routes/web.php
Route::get('/', HomePage::class)->name('home');
Route::get('/jobs/{id}', JobDetail::class)->name('job.detail');
Route::get('/login', App\Livewire\Auth\Login::class)->name('login');
Route::get('/register', App\Livewire\Auth\Register::class)->name('register');

// Auth required routes (ileride ekleyeceğimiz)
// Route::middleware('supabase.auth')->group(function () {
//     Route::post('/logout', function() {
//         session()->forget(['user_id', 'user_email', 'user_name', 'user_role', 'access_token']);
//         return redirect('/')->with('success', 'Başarıyla çıkış yaptınız.');
//     })->name('logout');
    
//     // İleride eklenecek:
//     // Route::get('/profile', ProfileComponent::class);
//     // Route::get('/dashboard', DashboardComponent::class);
// });
Route::get('/login', App\Livewire\Auth\Login::class)->name('login');
Route::get('/register', App\Livewire\Auth\Register::class)->name('register');
Route::prefix('api/ai')->group(function () {
    Route::post('/chat', function(Request $request) {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Giriş gerekli'
            ], 401);
        }
        
        $response = Http::timeout(30)->post(
            'https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat',
            array_merge($request->all(), [
                'user_id' => $userId,
                'session_id' => session()->getId()
            ])
        );

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json([
            'success' => false,
            'message' => 'AI servisi kullanılamıyor'
        ], 500);
    })->name('ai.chat');

    Route::get('/history', function() {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'messages' => []
            ]);
        }

        $response = Http::timeout(10)->get(
            "https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat/history/{$userId}"
        );

        return $response->successful() ? $response->json() : ['success' => false, 'messages' => []];
    })->name('ai.history');
    
});