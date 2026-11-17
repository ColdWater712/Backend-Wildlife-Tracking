<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Permission::with('roles');

        if ($request->filled('search')) {
            $query->where('permission_name', 'like', '%' . $request->search . '%');
        }

        return $this->paginated($query->paginate($request->input('per_page', 25)));
    }

    public function show($id)
    {
        return $this->success(Permission::with('roles')->findOrFail($id));
    }

    public function store(StorePermissionRequest $request)
    {
        $permission = Permission::create($request->validated());
        return $this->success($permission, 'Permission berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $request->validate([
            'permission_name' => 'sometimes|string|max:100',
            'description' => 'sometimes|nullable|string|max:255',
        ]);
        $permission->update($request->validated());
        return $this->success($permission->fresh(), 'Permission diperbarui');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        if ($permission->roles()->count() > 0) {
            return $this->error('Permission masih digunakan oleh role', 422);
        }
        $permission->delete();
        return $this->success(null, 'Permission dihapus');
    }
}