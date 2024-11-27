<?php

// use App\Http\Controllers\LoginController;

// use App\Http\Controllers\authentication\login\LoginController;

use App\Http\Controllers\authentication\login\LoginController;
use App\Http\Controllers\authentication\ResendRegistrationConfirmationEmail\ResendRegistrationConfirmationEmailController;
use Illuminate\Support\Facades\Route;

Route::get('test', function () {
    return view('welcome');
});

// Route::get('/auth', [LoginController::class, 'index'])->name('login');

// Route::get('/test/token', [LoginController::class, 'token']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/resendRegistrationConfirmationEmail', [ResendRegistrationConfirmationEmailController::class, 'resendRegistrationConfirmationEmail']);