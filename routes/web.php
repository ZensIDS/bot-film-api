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

// TWA View - Halaman utama Mini App
Route::get('/app', function () {
    return view('app');
});

// TWA View - Halaman checkout terpisah, agar mudah dicek terlepas dari halaman utama
Route::get('/checkout', function () {
    return view('checkout');
});
