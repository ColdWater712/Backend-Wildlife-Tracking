<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Permission::withCount('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name', 'asc');

        return $this->list($query, 'OK', $request);
    }

    public function show($id)
    {
        return $this->success(
            Permission::with(['roles'])->withCount('roles')->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name]);
        return $this->success($permission, 'Permission berhasil dibuat', 201);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|unique:permissions,name,' . $permission->permission_id . ',permission_id',
        ]);

        $permission->update($request->only(['name']));
        return $this->success($permission->fresh(), 'Permission diperbarui');
    }

    public function destroy($id)
    {
        Permission::findOrFail($id)->delete();
        return $this->success(null, 'Permission dihapus');
    }
}