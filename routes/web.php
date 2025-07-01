<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HomePage;
use App\Livewire\JobDetail;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', HomePage::class);
Route::get('/jobs/{id}', JobDetail::class)->name('job.detail');
