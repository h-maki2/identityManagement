<?php

namespace App\Http\Controllers\authentication\verifiedUpdate;

use Illuminate\Http\Request;
use packages\application\authentication\verifiedUpdate\update\VerifiedUpdateInputBoundary;

class VerifiedUpdateController
{
    private VerifiedUpdateInputBoundary $verifiedUpdateInputBoundary;

    public function __construct(VerifiedUpdateInputBoundary $verifiedUpdateInputBoundary)
    {
        $this->verifiedUpdateInputBoundary = $verifiedUpdateInputBoundary;
    }

    public function verifiedUpdate(Request $request)
    {
        $output = $this->verifiedUpdateInputBoundary->verifiedUpdate(
            $request->input('oneTimeTokenValue'),
            $request->input('oneTimePassword')
        );

        return $output->response();
    }
}