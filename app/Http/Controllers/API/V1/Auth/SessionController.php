<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        // Validate the request
        $attributes = Validator::make($request->all(), [
            'email' => 'required|string|email:rfc,dns|max:255|unique:users',
            'password' => 'required|string|min:6',
            'referral_code' => 'nullable|string'
        ]);

        if ($attributes->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => $attributes->errors(),
            ], 422);
        }

        // Validate email domain
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

            // Handle referral logic if applicable
            $referralUser = null;
            if (!empty($request->referral_code)) {
                $referralUser = User::where('invite_link', $request->referral_code)->first();
                if ($referralUser) {
                    // Logic for handling referral actions (e.g., sending an email)
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

            // Generate a JWT token for the registered user
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status_code' => 201,
                'message' => 'User registered successfully.',
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'message' => 'Registration failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * User login with JWT token generation.
     */
    public function login(Request $request)
    {
        // Validate login credentials
        $attributes = Validator::make($request->all(), [
            'email' => 'required|string|email:rfc,dns|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($attributes->fails()) {
            return response()->json([
                'status_code' => 422,
                'message' => $attributes->errors(),
            ], 422);
        }

        // Attempt to authenticate the user and generate a token
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Login successful.',
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status_code' => 200,
                'message' => 'User successfully logged out.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to logout, please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate the domain of the email.
     */
    private function isValidDomain($domain)
    {
        // Check if the domain matches a valid format
        $isValidFormat = preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $domain);

        // Check if the domain has valid DNS records
        if (!$isValidFormat || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
            return false;
        }

        return true;
    }

    /**
     * Generate a unique invite code for the user.
     */
    private function getInviteCode()
    {
        return bin2hex(random_bytes(5)); // Example of generating a random code
    
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
