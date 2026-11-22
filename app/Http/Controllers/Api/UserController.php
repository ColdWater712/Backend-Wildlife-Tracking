<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends ApiController
{
    public function index(Request $request)
    {
        $query = User::query()->with('role');

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }
        if ($request->filled('role')) {
            $query->whereHas('role', fn ($q) => $q->where('name_role', $request->role));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('username', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $this->applySort($query, $request, ['user_id', 'username', 'full_name', 'email', 'created_at'], 'created_at');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(AuthController::presentUser(User::with('role.permissions')->findOrFail($id)));
    }

    public function store(Request $request)
    {
        $this->mergeRoleName($request);
        $data = $request->validate([
            'username' => 'required|string|max:50|alpha_dash|unique:users,username',
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'string', Password::min(8)],
            'role_id' => 'required|integer|exists:roles,role_id',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        $user = User::create($data + ['status' => 'aktif']);

        return $this->success(AuthController::presentUser($user->load('role.permissions')), 'User berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $this->mergeRoleName($request);

        $data = $request->validate([
            'username' => ['sometimes', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->user_id, 'user_id')],
            'full_name' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'password' => ['sometimes', 'nullable', 'string', Password::min(8)],
            'role_id' => 'sometimes|integer|exists:roles,role_id',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['role_id']) && (int) $user->user_id === (int) $request->user()->user_id && (int) $data['role_id'] !== (int) $user->role_id) {
            return $this->error('Anda tidak dapat mengubah role akun sendiri.', 422);
        }

        $user->update($data);

        return $this->success(AuthController::presentUser($user->fresh('role.permissions')), 'User berhasil diperbarui');
    }

    public function updateStatus(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate(['status' => 'required|in:aktif,nonaktif']);

        if ((int) $user->user_id === (int) $request->user()->user_id) {
            return $this->error('Anda tidak dapat menonaktifkan akun sendiri.', 422);
        }

        DB::transaction(function () use ($user, $data) {
            $user->update($data);
            if ($data['status'] === 'nonaktif') {
                $user->tokens()->delete();
            }
        });

        return $this->success(AuthController::presentUser($user->fresh('role.permissions')), 'Status user diperbarui');
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->user_id === (int) $request->user()->user_id) {
            return $this->error('Anda tidak dapat menghapus akun sendiri.', 422);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->update(['status' => 'nonaktif']);
            $user->delete();
        });

        return $this->success(null, 'User dihapus');
    }

    public function me(Request $request)
    {
        return $this->success(AuthController::presentUser($request->user()->load('role.permissions')));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'full_name' => 'sometimes|string|max:100',
            'username' => ['sometimes', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->user_id, 'user_id')],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        return $this->success(AuthController::presentUser($user->fresh('role.permissions')), 'Profil diperbarui');
    }

    public function changePassword(Request $request)
    {
        $request->merge([
            'current_password' => $request->input('current_password', $request->input('old_password')),
            'password' => $request->input('password', $request->input('new_password')),
            'password_confirmation' => $request->input('password_confirmation', $request->input('new_password_confirmation')),
        ]);

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)],
        ]);

        $user = $request->user();
        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Password lama salah', 422, ['current_password' => ['Password lama salah.']]);
        }

        $user->update(['password' => $request->password]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();

        return $this->success(null, 'Password berhasil diubah');
    }

    public function tokens(int $id)
    {
        $user = User::findOrFail($id);

        return $this->list($user->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']));
    }

    public function revokeToken(int $id, int $tokenId)
    {
        $user = User::findOrFail($id);
        $deleted = $user->tokens()->where('id', $tokenId)->delete();

        return $deleted
            ? $this->success(null, 'Token dicabut')
            : $this->error('Token tidak ditemukan', 404);
    }

    public function activity(int $id)
    {
        User::withTrashed()->findOrFail($id);

        return $this->list(ActivityLog::query()
            ->where('user_id', $id)
            ->orderByDesc('timestamp')
            ->limit(200));
    }

    /** Terima `role` / `name_role` (nama) sebagai alternatif `role_id`. */
    private function mergeRoleName(Request $request): void
    {
        $name = $request->input('name_role', $request->input('role'));

        if (! $request->filled('role_id') && is_string($name) && $name !== '') {
            $request->merge(['role_id' => Role::where('name_role', $name)->value('role_id') ?? 0]);
        }
    }
}
