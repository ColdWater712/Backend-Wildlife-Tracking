<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $sort = $request->input('orderBy', 'user_id');
        $dir = $request->input('order', 'asc');
        $query->orderBy($sort, $dir);

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        return $this->success(User::with('role')->findOrFail($id));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id,
            'action' => 'CREATE_USER',
            'details' => json_encode(['user_id' => $user->user_id, 'email' => $user->email]),
            'timestamp' => now(),
        ]);

        return $this->success($user->load('role'), 'User berhasil dibuat', 201);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => $request->user()->user_id,
            'action' => 'UPDATE_USER',
            'details' => json_encode(['user_id' => $user->user_id]),
            'timestamp' => now(),
        ]);

        return $this->success($user->fresh()->load('role'), 'User berhasil diperbarui');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->user_id === auth()->id()) {
            return $this->error('Tidak dapat menghapus akun sendiri', 422);
        }

        // Soft delete
        $user->delete();

        // Log aktivitas
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'DELETE_USER',
            'details' => json_encode(['user_id' => $user->user_id]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'User berhasil dihapus (soft delete)');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:150|unique:users,email,' . $user->user_id . ',user_id',
        ]);
        $user->update($request->only(['name', 'email']));

        \App\Models\ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'UPDATE_PROFILE',
            'details' => json_encode(['fields' => array_keys($request->only(['name', 'email']))]),
            'timestamp' => now(),
        ]);

        return $this->success($user->fresh(), 'Profil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Password lama tidak sesuai', 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        \App\Models\ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'CHANGE_PASSWORD',
            'details' => json_encode(['ip' => $request->ip()]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'Password berhasil diubah');
    }

    // ✅ Endpoint baru: List user tokens
    public function getUserTokens($id)
    {
        $user = User::findOrFail($id);
        $tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'created_at')->get();
        return $this->success($tokens);
    }

    // ✅ Endpoint baru: Revoke specific token
    public function revokeUserToken($userId, $tokenId)
    {
        $user = User::findOrFail($userId);
        $token = $user->tokens()->findOrFail($tokenId);
        $token->delete();

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'REVOKE_TOKEN',
            'details' => json_encode(['user_id' => $userId, 'token_id' => $tokenId]),
            'timestamp' => now(),
        ]);

        return $this->success(null, 'Token berhasil dicabut');
    }
}