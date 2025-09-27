<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Difficulty;

class DifficultyController extends Controller
{
    /**
     * List difficulties.
     */
    public function index(Request $request)
    {
        $difficulties = Difficulty::orderBy('difficulty')->get();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Difficulties retrieved successfully.',
                'data'    => $difficulties,
            ], 200);
        }

        return view('admin.difficulties.index', compact('difficulties'));
    }

    /**
     * Store a new difficulty (used by your Configuration modal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'difficulty'         => ['required', 'integer', 'min:0'],
            'short_description'  => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:1000'],
        ]);

        $difficulty = Difficulty::create($validated);

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Difficulty created successfully.',
                'data'    => $difficulty,
            ], 201);
        }

        return redirect()->route('admin.difficulties.index')
                         ->with('success', 'Difficulty created successfully.');
    }

    /**
     * Show one difficulty.
     */
    public function show(Request $request, Difficulty $difficulty)
    {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Difficulty retrieved successfully.',
                'data'    => $difficulty,
            ], 200);
        }

        return view('admin.difficulties.show', compact('difficulty'));
    }

    /**
     * Update (inline Save button).
     */
    public function update(Request $request, Difficulty $difficulty)
    {
        $validated = $request->validate([
            'difficulty'         => ['sometimes', 'required', 'integer', 'min:0'],
            'short_description'  => ['sometimes', 'required', 'string', 'max:255'],
            'description'        => ['sometimes', 'nullable', 'string', 'max:1000'],
            'order'              => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $difficulty->fill($validated)->save();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Difficulty updated successfully.',
                'data'    => $difficulty->refresh(),
            ], 200);
        }

        return redirect()->route('admin.difficulties.index')
                         ->with('success', 'Difficulty updated successfully.');
    }

    /**
     * Delete.
     */
    public function destroy(Request $request, Difficulty $difficulty)
    {
        $difficulty->delete();

        if ($this->wantsJson($request)) {
            return response()->json(null, 204);
        }

        return redirect()->route('admin.difficulties.index')
                         ->with('success', 'Difficulty deleted successfully.');
    }

    /**
     * PATCH /admin/difficulties/{difficulty}/order
     */
    public function updateOrder(Request $request, Difficulty $difficulty)
    {
        $validated = $request->validate([
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $difficulty->order = $validated['order'];
        $difficulty->save();

        return response()->json([
            'message' => 'Difficulty order updated.',
            'data'    => $difficulty,
        ], 200);
    }

    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
