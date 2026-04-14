<?php

use Illuminate\Support\Facades\Route;

Route::get('login-home', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');
