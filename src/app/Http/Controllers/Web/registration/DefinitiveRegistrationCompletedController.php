<?php

namespace App\Http\Controllers\Web\authentication\definitiveRegistrationCompleted;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use packages\adapter\presenter\registration\definitiveRegistration\blade\BladeUserDefinitiveRegistrationPresenter;
use packages\adapter\view\registration\definitiveRegistration\blade\BladeUserDefinitiveRegistrationView;
use packages\application\authentication\UserDefinitiveRegistration\UserDefinitiveRegistrationInputBoundary;

class UserDefinitiveRegistrationController extends Controller
{
    public function definitiveRegistrationCompletedForm(Request $request)
    {
        return view('authentication.definitiveRegistrationCompleted.definitiveRegistrationCompletedForm', [
            'oneTimeToken' => $request->query('token', ''),
        ]);
    }

    public function definitiveRegistrationCompleted(
        Request $request,
        UserDefinitiveRegistrationInputBoundary $userDefinitiveRegistrationInputBoundary
    )
    {
        $output = $userDefinitiveRegistrationInputBoundary->handle(
            $request->input('oneTimeToken') ?? '',
            $request->input('oneTimePassword') ?? ''
        );

        $presenter = new BladeUserDefinitiveRegistrationPresenter($output);
        $view = new BladeUserDefinitiveRegistrationView($presenter);
        return $view->response();
    }
}