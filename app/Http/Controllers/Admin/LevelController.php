<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LevelController extends Controller
{
    /**
     * GET /admin/levels
     * JSON: return all levels (with status) ordered.
     * Non-JSON: redirect to the configuration page.
     */
    public function index(Request $request)
    {
        $levels = Level::with('status')
            ->when(schema_has_column('levels', 'order'), fn($q) => $q->orderBy('order'))
            ->orderBy('level')
            ->get();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Levels retrieved successfully.',
                'data' => $levels,
            ], 200);
        }

        return redirect()->route('admin.configuration.index');
    }

    /**
     * POST /admin/levels
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:0'],
            'start_maxile_level' => ['nullable', 'integer', 'min:0'],
            'end_maxile_level' => ['nullable', 'integer', 'min:0'],
            'status_id' => ['required', 'exists:statuses,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $level = Level::create($validated)->load('status');

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Level created successfully.',
                'data' => $level,
            ], 201);
        }

        return redirect()->route('admin.configuration.index')
            ->with('success', 'Level created successfully.');
    }

    /**
     * GET /admin/levels/{level}
     */
    public function show(Request $request, Level $level)
    {
        $level->load('status');

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Level retrieved successfully.',
                'data' => $level,
            ], 200);
        }

        return redirect()->route('admin.configuration.index');
    }

    /**
     * GET /admin/levels/{level}/edit
     * (Not used by your inline UI; kept for completeness.)
     */
    public function edit(Level $level)
    {
        return redirect()->route('admin.configuration.index');
    }

    /**
     * PUT /admin/levels/{level}
     * Supports partial updates; includes status_id.
     */
    public function update(Request $request, Level $level)
    {
        $validated = $request->validate([
            'level' => ['sometimes', 'required', 'integer', 'min:0'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'start_maxile_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'end_maxile_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status_id' => ['sometimes', 'required', 'exists:statuses'],
            'order' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $level->fill($validated)->save();

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Level updated successfully.',
                'data' => $level->refresh()->load('status'),
            ], 200);
        }

        return redirect()->route('admin.configuration.index')
            ->with('success', 'Level updated successfully.');
    }

    /**
     * DELETE /admin/levels/{level}
     */
    public function destroy(Request $request, Level $level)
    {
        $level->delete();

        if ($this->wantsJson($request)) {
            // 204 No Content is fine; returning a body is also fine for your UI.
            return response()->json(['message' => 'Level deleted successfully.'], 204);
        }

        return redirect()->route('admin.configuration.index')
            ->with('success', 'Level deleted successfully.');
    }

    /**
     * Optional helper for batch sort updates.
     * POST /admin/levels/reorder
     * Body: { "orders": [ { "id": 3, "order": 0 }, { "id": 5, "order": 1 } ] }
     */

    /**
     * Decide JSON vs Blade response.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
