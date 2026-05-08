<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/permissions/index', [
            'groups' => PermissionGroup::with('permissions')->orderBy('sort_order')->get(),
        ]);
    }

    public function groups(): Response
    {
        return Inertia::render('admin/permissions/groups', [
            'groups' => PermissionGroup::orderBy('sort_order')->get(),
        ]);
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|unique:permission_groups,key',
            'guard_name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        PermissionGroup::create($validated);

        return back()->with('success', 'تم إنشاء المجموعة بنجاح');
    }
}
