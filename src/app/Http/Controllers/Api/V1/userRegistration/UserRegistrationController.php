<?php

namespace App\Http\Controllers\Api\V1\userRegistration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use packages\application\userRegistration\UserRegistrationInputBoundary;

class UserRegistrationController extends Controller
{
    private UserRegistrationInputBoundary $userRegistrationInputBoundary;

    public function __construct(UserRegistrationInputBoundary $userRegistrationInputBoundary)
    {
        $this->userRegistrationInputBoundary = $userRegistrationInputBoundary;
    }

    public function userRegister(Request $request): mixed
    {
        $output = $this->userRegistrationInputBoundary->userRegister(
            $request->input('email', ''),
            $request->input('password', ''),
            $request->input('passwordConfirmation', '')
        );

        return $output->response();
    }
}