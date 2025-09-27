<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// If your model lives in App\Unit, change this import accordingly:
use App\Models\Unit;

class UnitController extends Controller
{
    /**
     * Display a listing of units (Blade by default).
     */
    public function index(Request $request)
    {
        $units = Unit::orderBy('id')->get();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Units retrieved successfully.',
                'data'    => $units,
            ], 200);
        }

        // Render your configuration Blade that includes the Units tab
        return view('admin.configuration.index', compact('units'));
    }

    /**
     * Show create form (rarely used if you create via modal+AJAX).
     * Keep it for full resource compliance.
     */
    public function create()
    {
        return view('admin.units.create');
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request)
    {
        // Minimal validation (replace with FormRequest if you have one)
        $validated = $request->validate([
            'unit'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $unit = Unit::create($validated);

        if ($this->wantsJson($request)) {
            // Matches your JS expectation: response.data
            return response()->json([
                'message' => 'Unit created successfully.',
                'data'    => $unit,
            ], 201);
        }

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified unit (Blade by default).
     */
    public function show(Request $request, Unit $unit)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Unit retrieved successfully.',
                'data'    => $unit,
            ], 200);
        }

        return view('admin.units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified unit (optional if inline edit).
     */
    public function edit(Unit $unit)
    {
        return view('admin.units.edit', compact('unit'));
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'unit'               => ['sometimes', 'required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:1000'],
        ]);

        $unit->fill($validated)->save();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Unit updated successfully.',
                'data'    => $unit->refresh(),
            ], 200);
        }

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Request $request, Unit $unit)
    {
        $unit->delete();

        if ($this->wantsJson($request)) {
            // 204 No Content for deletes
            return response()->json(null, 204);
        }

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    /**
     * Helper: decide JSON vs Blade based on request headers.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
