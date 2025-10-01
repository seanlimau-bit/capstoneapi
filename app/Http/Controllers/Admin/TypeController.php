<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// If your model is App\Type, change the import accordingly:
use App\Models\Type;

class TypeController extends Controller
{
    /**
     * List all types.
     */
    public function index(Request $request)
    {
        $types = Type::orderBy('id')->get();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Types retrieved successfully.',
                'data'    => $types,
            ], 200);
        }

        // If you also have a standalone page:
        return view('admin.types.index', compact('types'));
    }

    /**
     * Show create form (optional; Configuration page uses a modal).
     */
    public function create()
    {
        return view('admin.types.create');
    }

    /**
     * Store a new type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status_id'   => ['sometimes', 'required', 'integer', 'exists:statuses'], // ✅ ADD THIS

        ]);

        $type = Type::create($validated);

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Type created successfully.',
                'data'    => $type,
            ], 201);
        }

        return redirect()
            ->route('admin.types.index')
            ->with('success', 'Type created successfully.');
    }

    /**
     * Show a single type.
     */
    public function show(Request $request, Type $type)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Type retrieved successfully.',
                'data'    => $type,
            ], 200);
        }

        return view('admin.types.show', compact('type'));
    }

    /**
     * Edit form (optional; you inline-edit in the table).
     */
    public function edit(Type $type)
    {
        return view('admin.types.edit', compact('type'));
    }

    /**
     * Update a type (used by your inline Save).
     */
    public function update(Request $request, Type $type)
    {
        $validated = $request->validate([
            'type'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $type->fill($validated)->save();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Type updated successfully.',
                'data'    => $type->refresh(),
            ], 200);
        }

        return redirect()
            ->route('admin.types.index')
            ->with('success', 'Type updated successfully.');
    }

    /**
     * Delete a type (used by your Delete button).
     */
    public function destroy(Request $request, Type $type)
    {
        $type->delete();

        if ($this->wantsJson($request)) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('admin.types.index')
            ->with('success', 'Type deleted successfully.');
    }

    /**
     * Helper: decide JSON vs Blade.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
