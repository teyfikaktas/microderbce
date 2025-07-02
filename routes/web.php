<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

// Livewire components
use App\Livewire\HomePage;
use App\Livewire\JobDetail;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\CompanyRegister;

// Admin controllers
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\CompanyController;

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
| Admin Routes (Job / Company / User Management)
|--------------------------------------------------------------------------
| AdminController içindeki ensureAdmin() metodu ile erişim kontrolü yapılır.
*/
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', function () {
            return redirect()->route('admin.jobs.index');
        })->name('dashboard');

        // Job Postings CRUD
        Route::get('jobs',         [JobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/create',  [JobController::class, 'create'])->name('jobs.create');
        Route::post('jobs',        [JobController::class, 'store'])->name('jobs.store');
        Route::get('jobs/{id}/edit',[JobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{id}',    [JobController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{id}', [JobController::class, 'destroy'])->name('jobs.destroy');

        // Companies CRUD
        Route::get('companies',           [CompanyController::class, 'index']) ->name('companies.index');
        Route::get('companies/create',    [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies',          [CompanyController::class, 'store']) ->name('companies.store');
        Route::get('companies/{id}/edit', [CompanyController::class, 'edit'])  ->name('companies.edit');
        Route::put('companies/{id}',      [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('companies/{id}',   [CompanyController::class, 'destroy'])->name('companies.destroy');

        // Users management stub (ileride UserController eklenebilir)
        Route::get('users', function() {
            return view('admin.users.index');
        })->name('users.index');
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
