<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statuses = Status::all();
        return response()->json($statuses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:255|unique:statuses,status',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_visible' => 'nullable|boolean'
        ]);

        $validated['is_visible'] = $validated['is_visible'] ?? true;

        $status = Status::create($validated);
        
        return response()->json([
            'message' => 'Status created successfully',
            'data' => $status
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Status $status)
    {
        return response()->json($status);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Status $status)
    {
        return response()->json($status);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Status $status)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:255|unique:statuses,status,' . $status->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'is_visible' => 'nullable|boolean'
        ]);

        $status->update($validated);
        
        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $status
        ]);
    }

    /**
     * Update only specific fields (for inline editing)
     */
    public function patch(Request $request, Status $status)
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|max:255|unique:statuses,status,' . $status->id,
            'description' => 'sometimes|nullable|string',
            'color' => 'sometimes|nullable|string|max:7',
            'icon' => 'sometimes|nullable|string|max:255',
            'is_visible' => 'sometimes|boolean'
        ]);

        $status->update($validated);
        
        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $status
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Status $status)
    {
        // Check if status is being used
        $questionsCount = \DB::table('questions')->where('status_id', $status->id)->count();
        
        if ($questionsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete status. It is being used by ' . $questionsCount . ' questions.'
            ], 422);
        }

        $status->delete();
        
        return response()->json([
            'message' => 'Status deleted successfully'
        ]);
    }

    /**
     * Bulk update statuses order (if needed)
     */
    public function bulkUpdateOrder(Request $request)
    {
        $validated = $request->validate([
            'statuses' => 'required|array',
            'statuses.*.id' => 'required|exists:statuses,id',
            'statuses.*.order' => 'required|integer|min:0'
        ]);

        foreach ($validated['statuses'] as $statusData) {
            Status::where('id', $statusData['id'])
                ->update(['order' => $statusData['order']]);
        }

        return response()->json([
            'message' => 'Order updated successfully'
        ]);
    }
}