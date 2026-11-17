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
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.", 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Email atau password salah.', 401, [
                'credentials' => ['Email atau password salah.'],
            ]);
        }

        RateLimiter::clear($throttleKey);
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

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