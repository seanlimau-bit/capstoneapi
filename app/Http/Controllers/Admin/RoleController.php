<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// If your Role model lives in App\Role, change this import accordingly:
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * List roles (Blade by default; JSON for XHR).
     */
    public function index(Request $request)
    {
        $roles = Role::withCount('users')->orderBy('id')->get();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Roles retrieved successfully.',
                'data'    => $roles,
            ], 200);
        }

        // If roles are shown on your configuration page, you likely render that page elsewhere.
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show create form (optional if you add via modal/AJAX).
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * Store role. Authorization handled by route middleware (auth + admin).
     * If you have a FormRequest (CreateRoleRequest), swap it in and use ->validated().
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = Role::create($validated);

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Role created successfully.',
                'data'    => $role,
            ], 201);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Show single role.
     */
    public function show(Request $request, Role $role)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Role retrieved successfully.',
                'data'    => $role,
            ], 200);
        }

        return view('admin.roles.show', compact('role'));
    }

    /**
     * Edit form (optional).
     */
    public function edit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'role'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role->fill($validated)->save();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Role updated successfully.',
                'data'    => $role->refresh(),
            ], 200);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Delete role.
     */
    public function destroy(Request $request, Role $role)
    {
        $role->delete();

        if ($this->wantsJson($request)) {
            return response()->json(null, 204);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    /**
     * GET /admin/roles/{role}/permissions
     * Your Blade calls this to load the checkboxes.
     * Return an array of permission strings the role currently has.
     */
    public function permissions(Request $request, Role $role)
    {
        // Adjust to your storage: e.g., $role->permissions()->pluck('name')->toArray()
        $perms = method_exists($role, 'permissions')
            ? $role->permissions()->pluck('name')->toArray()
            : (array) ($role->permissions ?? []); // if stored as JSON column

        return response()->json([
            'permissions' => $perms,
        ], 200);
    }

    /**
     * POST /admin/roles/{role}/permissions
     * Save selected permissions coming from your modal.
     */
    public function savePermissions(Request $request, Role $role)
    {
        $permissions = (array) $request->input('permissions', []);

        // Persist according to your design:
        // - via pivot: sync permission IDs by mapping names->ids
        // - or JSON column on roles: $role->permissions = $permissions;
        if (method_exists($role, 'permissions')) {
            // Example if you have a Permission model with 'name' field:
            // $ids = \App\Models\Permission::whereIn('name', $permissions)->pluck('id')->toArray();
            // $role->permissions()->sync($ids);
            // For now, stub to avoid breaking:
            $role->setAttribute('permissions', $permissions);
        } else {
            $role->setAttribute('permissions', $permissions);
        }

        $role->save();

        return response()->json(['message' => 'Permissions updated.'], 200);
    }

    /**
     * Helper to decide JSON vs Blade.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
