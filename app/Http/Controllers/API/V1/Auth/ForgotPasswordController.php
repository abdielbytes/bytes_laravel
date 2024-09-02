<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink($request->only('email'));

        return $response == Password::RESET_LINK_SENT
                    ? response()->json(['message' => __($response)], 200)
                    : response()->json(['error' => __($response)], 400);
    }
}
