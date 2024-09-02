<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\ReferralMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    public function register(Request $request)
    {
        $attributes = Validator::make($request->all(), [
            'email' => 'required|string|email:rfc|max:255|unique:users',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string',
        ]);

        if ($attributes->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => $attributes->errors(),
            ], 422);
        }

        $email = $request->email;
        $emailParts = explode('@', $email);
        $domain = array_pop($emailParts);

        if (!$this->isValidDomain($domain)) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Invalid email address.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Handle referral code if provided
            $referralUser = null;
            if (!empty($request->referral_code)) {
                $referral_code = $request->referral_code;
                $referralUser = User::where('invite_link', $referral_code)->first();

                if ($referralUser) {
                    Mail::to($referralUser->email)->queue(new ReferralMail($referralUser->name));
                }
            }

            // Creating the user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'invite_link' => $this->getInviteCode(),
                'ref_by' => $referralUser->id ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 201,
                'message' => 'User registered successfully.',
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => 500,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    private function isValidDomain($domain)
    {
        // Check if the domain matches a valid domain format using regex
        $isValidFormat = preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $domain);
    
        if (!$isValidFormat) {
            return false;
        }
    
        // Check if the domain has a valid MX or A record
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return false;
        }
    
        return true;
    }
    
    private function getInviteCode()
    {
        // Generate a unique invite link
        return bin2hex(random_bytes(5)); // Example code generation


        /*
        $length = 8;
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = substr(str_shuffle($characters), 0, $length);
        } while (User::where('invite_link', $code)->exists());

        return $code;
    */
    }
}
