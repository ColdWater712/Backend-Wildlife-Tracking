<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                'data' => null,
                'errors' => null,
            ], 429);
        }

        $user = User::where('email', $request->email)->first();
        $passwordValid = $user && Hash::check($request->password, $user->password);

        if (!$user || !$passwordValid) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'data' => null,
                'errors' => ['credentials' => ['Email atau password salah.']],
            ], 401);
        }

        RateLimiter::clear($throttleKey);
        
        // Hapus token lama
        $user->tokens()->delete();
        
        // Buat token baru
        $token = $user->createToken('api-token')->plainTextToken;

        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'LOGIN',
            'details' => json_encode(['ip' => $request->ip()]),
            'timestamp' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_id' => $user->role_id,
                ],
                'token' => $token,
            ],
            'errors' => null,
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'LOGOUT',
            'details' => json_encode(['ip' => $request->ip()]),
            'timestamp' => now(),
        ]);

        // Revoke token saat ini
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
            'errors' => null,
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $request->user()->load('role'),
            'errors' => null,
        ], 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Hapus token lama
        $request->user()->currentAccessToken()->delete();
        
        // Buat token baru
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil di-refresh.',
            'data' => [
                'token' => $token,
            ],
            'errors' => null,
        ], 200);
    }
}