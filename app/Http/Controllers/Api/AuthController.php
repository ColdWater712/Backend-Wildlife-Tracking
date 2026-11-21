<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // ✅ PERBAIKAN 1: Eager load 'role' untuk mencegah N+1 query
        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user->status !== 'aktif') {
            return $this->error('Akun Anda tidak aktif. Hubungi administrator.', 403);
        }

        // Hapus token lama agar tidak menumpuk (best practice untuk mobile/desktop app)
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        // ✅ PERBAIKAN 2: Pastikan response mengembalikan username dan full_name secara eksplisit
        return $this->success([
            'user' => [
                'user_id'   => $user->user_id,
                'username'  => $user->username,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'status'    => $user->status,
                'role'      => $user->role, 
            ],
            'token' => $token,
        ], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logout berhasil');
    }

    public function refresh(Request $request)
    {
        $user = $request->user()->load('role');
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => [
                'user_id'   => $user->user_id,
                'username'  => $user->username,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'status'    => $user->status,
                'role'      => $user->role,
            ],
            'token' => $token,
        ], 'Token berhasil di-refresh');
    }

    public function me(Request $request)
    {
        // ✅ Eager load role dan permissions
        $user = $request->user()->load(['role', 'permissions']);
        
        return $this->success([
            'user_id'     => $user->user_id,
            'username'    => $user->username,
            'full_name'   => $user->full_name,
            'email'       => $user->email,
            'status'      => $user->status,
            'role'        => $user->role,
            'permissions' => $user->permissions,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'full_name' => 'required|string|max:100',
            'role_id'   => 'required|exists:roles,role_id',
        ]);

        $user = User::create([
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'full_name' => $request->full_name,
            'role_id'   => $request->role_id,
            'status'    => 'aktif',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => [
                'user_id'   => $user->user_id,
                'username'  => $user->username,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'status'    => $user->status,
                'role'      => $user->role()->first(), 
            ],
            'token' => $token,
        ], 'Registrasi berhasil', 201);
    }
}