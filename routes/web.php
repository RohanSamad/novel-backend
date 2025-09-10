<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary debug route
Route::get('/phpinfo', function () {
    phpinfo();
});
