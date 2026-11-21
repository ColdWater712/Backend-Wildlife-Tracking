<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
<<<<<<< HEAD
        public function login(LoginRequest $request)
=======
    use ApiResponse;

    public function login(LoginRequest $request)
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.", 429);
        }

        // 1. PERBAIKAN: Tambahkan ->with('role') untuk eager loading
        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Email atau password salah.', 401, [
                'credentials' => ['Email atau password salah.'],
            ]);
        }

        RateLimiter::clear($throttleKey);
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

<<<<<<< HEAD
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
=======
        \App\Models\ActivityLog::create([
            'user_id'   => $user->user_id,
            'action'    => 'LOGIN',
            'details'   => json_encode(['ip' => $request->ip()]),
            'timestamp' => now(),
        ]);

        // ✅ data.user berisi username + full_name (bukan cuma name)
        return $this->success([
            'user'  => $this->presentUser($user),
            'token' => $token,
        ], 'Login berhasil.');
>>>>>>> 8e7e8e56ec983b5e0342baf64cb79f61cc79443d
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        \App\Models\ActivityLog::create([
            'user_id'   => $request->user()->user_id,
            'action'    => 'LOGOUT',
            'details'   => json_encode(['ip' => $request->ip()]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'Logout berhasil.');
    }

    public function me(Request $request)
    {
        return $this->success($this->presentUser($request->user()));
    }

    public function refresh(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('api-token')->plainTextToken;
        return $this->success(['token' => $token], 'Token berhasil di-refresh.');
    }
}