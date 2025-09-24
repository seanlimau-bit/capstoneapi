<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Difficulty;
use Illuminate\Http\Request;

class DifficultyController extends Controller
{
    public function index()
    {
        return Difficulty::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'short_description' => 'required|string|max:255',
            'long_description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0'
        ]);

        return Difficulty::create($validated);
    }

    public function show(Difficulty $difficulty)
    {
        return $difficulty;
    }

    public function edit(Difficulty $difficulty)
    {
        return $difficulty;
    }

    public function update(Request $request, Difficulty $difficulty)
    {
        $validated = $request->validate([
            'short_description' => 'required|string|max:255',
            'long_description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0'
        ]);

        $difficulty->update($validated);
        return $difficulty;
    }

    public function destroy(Difficulty $difficulty)
    {
        $difficulty->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function updateOrder(Request $request, Difficulty $difficulty)
    {
        $validated = $request->validate([
            'order' => 'required|integer|min:0'
        ]);

        $difficulty->update(['order' => $validated['order']]);
        return response()->json(['message' => 'Order updated']);
    }
}