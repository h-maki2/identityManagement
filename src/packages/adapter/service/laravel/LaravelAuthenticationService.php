<?php

namespace packages\adapter\service\laravel;

use Illuminate\Support\Facades\Auth;
use packages\domain\model\authenticationInformation\AuthenticationService;
use packages\domain\model\authenticationInformation\UserId;

class LaravelAuthenticationService implements AuthenticationService
{
    public function markAsLoggedIn(UserId $userId): void
    {
        Auth::guard('api')->loginUsingId($userId->value);
    }

    public function loggedInUserId(): ?UserId
    {
        if (Auth::check()) {
            return new UserId(Auth::id());
        }

        return null;
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }
}