<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name_role', 'like', "%{$search}%");
        }

        $query->orderBy('name_role', 'asc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            Role::with(['permissions', 'users'])->withCount('users')->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_role'         => 'required|string|unique:roles,name_role',
            'role_permission_id' => 'nullable|integer',
        ]);

        $role = Role::create([
            'name_role'         => $request->name_role,
            'role_permission_id' => $request->role_permission_id,
        ]);

        return $this->success($role->load(['permissions']), 'Role berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'name_role' => 'sometimes|string|unique:roles,name_role,' . $role->role_id . ',role_id',
        ]);

        $role->update($request->only(['name_role']));
        return $this->success($role->fresh(['permissions']), 'Role diperbarui');
    }

    public function destroy($id)
    {
        Role::findOrFail($id)->delete();
        return $this->success(null, 'Role dihapus');
    }

    public function getPermissions($id)
    {
        $role = Role::findOrFail($id);
        return $this->success($role->permissions);
    }

    public function syncPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate(['permissions' => 'required|array']);

        $role->permissions()->sync($request->permissions);
        return $this->success($role->load(['permissions']), 'Permissions disinkronkan');
    }

    public function assignPermission($id, $permissionId)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->attach($permissionId);
        return $this->success($role->load(['permissions']), 'Permission ditambahkan ke role');
    }

    public function revokePermission($id, $permissionId)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->detach($permissionId);
        return $this->success($role->load(['permissions']), 'Permission dihapus dari role');
    }
}