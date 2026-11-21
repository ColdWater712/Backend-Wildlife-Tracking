<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
        public function login(LoginRequest $request)
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        // 1. PERBAIKAN: Tambahkan ->with('role') untuk eager loading
        $user = User::with('role')->where('email', $request->email)->first();

        $passwordValid = $user && Hash::check($request->password, $user->password);

        if (!$user || !$passwordValid) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'errors' => [
                    'credentials' => ['Email atau password salah.'],
                ],
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // 2. PERBAIKAN: Ambil role_name dari relasi, fallback ke null jika tidak ada
                    'role' => $user->role ? $user->role->role_name : null,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }
}