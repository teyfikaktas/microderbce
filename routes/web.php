<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\HomePage;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', HomePage::class);
