<?php

// use App\Http\Controllers\LoginController;

// use App\Http\Controllers\authentication\login\LoginController;

use App\Http\Controllers\authentication\login\LoginController;
use App\Http\Controllers\authentication\resendRegistrationConfirmationEmail\ResendRegistrationConfirmationEmailController;
use App\Http\Controllers\authentication\verifiedUpdate\DisplayVerifiedUpdatePageController;
use App\Http\Controllers\authentication\verifiedUpdate\VerifiedUpdateController;
use App\Http\Controllers\userRegistration\UserRegistrationController;
use App\Services\ApiVersionResolver;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:api')->get('/user/profile', [UserProfileController::class, 'show']);

Route::middleware(['api.version'])->group(function () {
    Route::get('/verifiedUpdate', function (Request $request, ApiVersionResolver $resolver, Container $container) {
        $version = $request->attributes->get('api_version');
        $controller = $resolver->resolve($version, 'DisplayVerifiedUpdatePageController');

        return $container->call([$controller, 'displayVerifiedUpdatePage']);
    });

    Route::post('/verifiedUpdate', function (Request $request, ApiVersionResolver $resolver, Container $container) {
        $version = $request->attributes->get('api_version');
        $controller = $resolver->resolve($version, 'VerifiedUpdateController');
        return $container->call([$controller, 'verifiedUpdate']);
    });

    Route::post('/login', function (Request $request, ApiVersionResolver $resolver, Container $container) {
        $version = $request->attributes->get('api_version');
        $controller = $resolver->resolve($version, 'LoginController');

        return $container->call([$controller, 'login']);
    });

    Route::post('/resendRegistrationConfirmationEmail', function (Request $request, ApiVersionResolver $resolver, Container $container) {
        $version = $request->attributes->get('api_version');
        $controller = $resolver->resolve($version, 'ResendRegistrationConfirmationEmailController');

        return $container->call([$controller, 'resendRegistrationConfirmationEmail']);
    });

    Route::post('/userRegistration', function (Request $request, ApiVersionResolver $resolver, Container $container) {
        $version = $request->attributes->get('api_version');
        $controller = $resolver->resolve($version, 'UserRegistrationController');

        return $container->call([$controller, 'userRegister']);
    });
});

Route::middleware(['api.version', 'auth:api'])->group(function () {
    // 認証が必要なAPIのルーティング
});