<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-seeder-a83f', function () {
    try {
        Artisan::call('db:seed', ['--force' => true]);
        return 'Seeder selesai dijalankan. Output: ' . Artisan::output();
    } catch (\Throwable $e) {
        return 'Error: ' . $e->getMessage();
    }
});
