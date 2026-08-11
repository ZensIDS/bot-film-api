<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// TWA View - MiniApp
Route::get('/app', function () {
    return view('app');
});

// TWA View - Checkout Page
Route::get('/checkout', function () {
    return view('checkout');
});

// TWA View - Halaman detail film
Route::get('/movie/{id}', function ($id) {
    return view('movie-detail', ['id' => $id]);
});

// TWA View - Halaman request judul film (khusus pelanggan aktif)
Route::get('/request-film', function () {
    return view('request-film');
});
