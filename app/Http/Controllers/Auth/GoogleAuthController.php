<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AppUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'sub' => 'required',
                'name' => 'required',
            ]);
    
            $user = AppUser::firstOrNew(['email' => $request->email]);
            $user->fill([
                'google_id' => $request->sub,
                'name' => $request->name,
                'first_name' => $request->given_name,
                'last_name' => $request->family_name,
                'avatar' => $request->picture,
                'email_verified_at' => $request->email_verified ? now() : null,
                'password' => Hash::make(Str::random(16)),
                'provider' => 'MOBILE',
            ])->save();
    
            // For Passport:
            $tokenResult = $user->createToken('google-auth-token');
            $token = $tokenResult->accessToken;
    
            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at
            ]);
    
        } catch (\Exception $e) {
            Log::error('Google auth error: '.$e->getMessage());
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => bcrypt(Str::random(16)),
                    'google_id' => $googleUser->id,
                ]);
            }
            
            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}