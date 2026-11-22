<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use Illuminate\Http\Request;

/** Permission didefinisikan lewat seeder (read-only via API) agar kode & DB tetap sinkron. */
class PermissionController extends ApiController
{
    public function index(Request $request)
    {
        $query = Permission::query()->withCount('roles');

        if ($request->filled('search')) {
            $query->where('name_permission', 'like', '%' . $request->search . '%');
        }

        return $this->list($query->orderBy('permission_id'));
    }

    public function show(int $id)
    {
        return $this->success(Permission::with('roles')->withCount('roles')->findOrFail($id));
    }
}
