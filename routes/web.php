<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HomePage;
use App\Livewire\JobDetail;
use Illuminate\Support\Facades\Http;

// Route::get('/', function () {
//     return view('welcome');
// });
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
