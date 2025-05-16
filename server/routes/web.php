<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

Route::get('/assets/{path}', function ($path) {
    $fullPath = public_path("media/{$path}");

    if (!File::exists($fullPath)) {
        abort(404);
    }
    $mimeType = File::mimeType($fullPath);
    return response()->file($fullPath, [
        'Access-Control-Allow-Origin' => '*',
        'Content-Type' => $mimeType
    ]);
})->where('path', '.*');
