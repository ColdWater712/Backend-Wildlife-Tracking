<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login User
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Cek apakah user ada dan password cocok
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid.',
                'data' => null,
                'errors' => ['credentials' => ['Email atau password salah.']]
            ], 401);
        }

        // Hapus token lama (opsional, tapi disarankan untuk keamanan)
        $user->tokens()->delete();

        // Buat token baru
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ],
            'errors' => null
        ], 200);
    }

    /**
     * Logout User
     */
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
            'errors' => null
        ], 200);
    }

    /**
     * Get Current User (Me)
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data user saat ini.',
            'data' => $request->user(),
            'errors' => null
        ], 200);
    }
}