<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Livewire\HomePage;
use App\Livewire\JobDetail;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\CompanyRegister;
use App\Http\Controllers\Admin\JobController;

/*
|--------------------------------------------------------------------------
| Public Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', HomePage::class)->name('home');
Route::get('/jobs/{id}', JobDetail::class)->name('job.detail');

Route::get('/login', Login::class)->name('login');
Route::get('/register', Register::class)->name('register');
Route::get('/company-register', CompanyRegister::class)->name('company.register');

Route::match(['GET','POST'], '/logout', function () {
    auth()->logout();
    return redirect()->route('home');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (Job Management)
|--------------------------------------------------------------------------
|
| Erişim kontrolü JobController içindeki ensureAdmin() ile yapılır.
|
*/
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/create', [JobController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [JobController::class, 'store'])->name('jobs.store');
        Route::get('jobs/{id}/edit', [JobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{id}', [JobController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{id}', [JobController::class, 'destroy'])->name('jobs.destroy');
    });

/*
|--------------------------------------------------------------------------
| AI Chat API Proxy
|--------------------------------------------------------------------------
*/
Route::prefix('api/ai')->group(function () {
    Route::post('/chat', function (Request $request) {
        $userId = session('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'Giriş gerekli'], 401);
        }

        $response = Http::timeout(30)
            ->post('https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat', array_merge(
                $request->all(),
                ['user_id' => $userId, 'session_id' => session()->getId()]
            ));

        return $response->successful()
            ? $response->json()
            : response()->json(['success' => false, 'message' => 'AI servisi kullanılamıyor'], 500);
    })->name('ai.chat');

    Route::get('/history', function () {
        $userId = session('user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'messages' => []]);
        }

        $response = Http::timeout(10)
            ->get("https://ai-api.elastic-swartz.213-238-168-122.plesk.page/api/chat/history/{$userId}");

        return $response->successful()
            ? $response->json()
            : ['success' => false, 'messages' => []];
    })->name('ai.history');
});
