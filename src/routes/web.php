<?php

use App\Http\Controllers\Web\authentication\definitiveRegistrationCompleted\definitiveRegistrationCompletedController;
use App\Http\Controllers\Web\userRegistration\UserRegistrationController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/userRegistration', [UserRegistrationController::class, 'userRegisterForm']);
Route::post('/userRegistration', [UserRegistrationController::class, 'userRegister']);

Route::get('/DefinitiveRegistrationCompleted', [DefinitiveRegistrationCompletedController::class, 'DefinitiveRegistrationCompletedForm']);
Route::post('/DefinitiveRegistrationCompleted', [DefinitiveRegistrationCompletedController::class, 'DefinitiveRegistrationCompleted']);

Route::middleware(['api.version', 'auth:api'])->group(function () {
    // 認証が必要なAPIのルーティング
});