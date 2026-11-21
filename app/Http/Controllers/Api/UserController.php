<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with(['role']);

        if ($request->filled('role_id')) $query->where('role_id', $request->role_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('is_active')) $query->where('status', $request->is_active ? 'aktif' : 'nonaktif');

        $query->orderBy('created_at', 'desc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(User::with(['role', 'permissions'])->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:100',
            'role_id'  => 'required|exists:roles,role_id',
            'address'  => 'nullable|string',
            'phone'    => 'nullable|string',
        ]);

        $user = User::create([
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'full_name' => $request->full_name,
            'role_id'   => $request->role_id,
            'address'   => $request->address,
            'phone'     => $request->phone,
            'status'    => 'aktif',
        ]);

        return $this->success($user->load(['role']), 'User berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username'  => ['sometimes', 'string', 'max:50', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'email'     => ['sometimes', 'email', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'full_name' => 'sometimes|string|max:100',
            'role_id'   => 'sometimes|exists:roles,role_id',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string',
        ]);

        $user->update($request->only(['username', 'email', 'full_name', 'role_id', 'address', 'phone']));

        return $this->success($user->fresh(['role']), 'User berhasil diperbarui');
    }

    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate(['status' => 'required|in:aktif,nonaktif']);
        $user->update(['status' => $request->status]);
        return $this->success($user->fresh(), 'Status user diperbarui');
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return $this->success(null, 'User dihapus');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'full_name' => 'sometimes|string|max:100',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string',
        ]);
        $user->update($request->only(['full_name', 'address', 'phone']));
        return $this->success($user->fresh(['role']), 'Profil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->error('Password lama salah', 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        return $this->success(null, 'Password berhasil diubah');
    }

    public function getUserTokens($id)
    {
        $user = User::findOrFail($id);
        return $this->success($user->tokens()->get());
    }

    public function revokeUserToken($id, $tokenId)
    {
        $user = User::findOrFail($id);
        $user->tokens()->where('id', $tokenId)->delete();
        return $this->success(null, 'Token dicabut');
    }

    public function userActivity($id)
    {
        $logs = ActivityLog::where('user_id', $id)
            ->orderBy('timestamp', 'desc')
            ->limit(100)
            ->get();
        return $this->list($logs);
    }
}