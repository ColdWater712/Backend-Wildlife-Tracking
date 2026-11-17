<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Role::with('permissions');

        if ($request->filled('search')) {
            $query->where('role_name', 'like', '%' . $request->search . '%');
        }

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        return $this->success(Role::with('permissions')->findOrFail($id));
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create($request->validated());
        return $this->success($role, 'Role berhasil dibuat', 201);
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->update($request->validated());
        return $this->success($role->fresh(), 'Role berhasil diperbarui');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if ($role->users()->count() > 0) {
            return $this->error('Role masih digunakan oleh user', 422);
        }
        $role->delete();
        return $this->success(null, 'Role berhasil dihapus');
    }

    public function getPermissions($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return $this->success($role->permissions);
    }

    public function syncPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,permission_id',
        ]);
        $role->permissions()->sync($request->permission_ids);
        return $this->success($role->fresh()->load('permissions'), 'Permissions disinkronkan');
    }

    public function assignPermission($id, $permissionId)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->syncWithoutDetaching([$permissionId]);
        return $this->success(null, 'Permission ditambahkan');
    }

    public function revokePermission($id, $permissionId)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->detach($permissionId);
        return $this->success(null, 'Permission dicabut');
    }
}