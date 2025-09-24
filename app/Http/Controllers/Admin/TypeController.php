<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TypeController extends Controller
{
    /**
     * Display a listing of question types
     */
    public function index()
    {
        $types = Type::withCount('questions')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.types.index', compact('types'));
    }

    /**
     * Show the form for creating a new type
     */
    public function create()
    {
        return view('admin.types.create');
    }

    /**
     * Store a newly created type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50|unique:types,type',
            'description' => 'nullable|string|max:255',
            'input_type' => 'required|in:multiple_choice,number,text,boolean,fill_blank',
            'answer_format' => 'nullable|string|max:100',
            'max_answers' => 'nullable|integer|min:1|max:10',
            'requires_image' => 'boolean',
            'calculator_allowed' => 'boolean',
            'is_active' => 'boolean'
        ]);

        try {
            $type = Type::create([
                'type' => $validated['type'],
                'description' => $validated['description'],
                'input_type' => $validated['input_type'],
                'answer_format' => $validated['answer_format'],
                'max_answers' => $validated['max_answers'] ?? 4,
                'requires_image' => $validated['requires_image'] ?? false,
                'calculator_allowed' => $validated['calculator_allowed'] ?? false,
                'is_active' => $validated['is_active'] ?? true
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question type created successfully',
                    'type' => $type
                ]);
            }

            return redirect()->route('admin.types.index')
                ->with('success', 'Question type created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create question type', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create question type'
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to create question type');
        }
    }

    /**
     * Display the specified type
     */
    public function show(Type $type)
    {
        $type->loadCount('questions');
        
        // Get sample questions using this type
        $sampleQuestions = Question::where('type_id', $type->id)
            ->with(['skill', 'difficulty'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.types.show', compact('type', 'sampleQuestions'));
    }

    /**
     * Show the form for editing the type
     */
    public function edit(Type $type)
    {
        return view('admin.types.edit', compact('type'));
    }

    /**
     * Update the specified type
     */
    public function update(Request $request, Type $type)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50|unique:types,type,' . $type->id,
            'description' => 'nullable|string|max:255',
            'input_type' => 'required|in:multiple_choice,number,text,boolean,fill_blank',
            'answer_format' => 'nullable|string|max:100',
            'max_answers' => 'nullable|integer|min:1|max:10',
            'requires_image' => 'boolean',
            'calculator_allowed' => 'boolean',
            'is_active' => 'boolean'
        ]);

        try {
            $type->update([
                'type' => $validated['type'],
                'description' => $validated['description'],
                'input_type' => $validated['input_type'],
                'answer_format' => $validated['answer_format'],
                'max_answers' => $validated['max_answers'] ?? $type->max_answers,
                'requires_image' => $validated['requires_image'] ?? false,
                'calculator_allowed' => $validated['calculator_allowed'] ?? false,
                'is_active' => $validated['is_active'] ?? true
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question type updated successfully',
                    'type' => $type
                ]);
            }

            return redirect()->route('admin.types.index')
                ->with('success', 'Question type updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update question type', [
                'type_id' => $type->id,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update question type'
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to update question type');
        }
    }

    /**
     * Remove the specified type
     */
    public function destroy(Request $request, Type $type)
    {
        try {
            // Check if type is being used
            $questionCount = Question::where('type_id', $type->id)->count();
            
            if ($questionCount > 0) {
                $message = "Cannot delete this type. It's being used by {$questionCount} questions.";
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }

                return back()->with('error', $message);
            }

            $type->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question type deleted successfully'
                ]);
            }

            return redirect()->route('admin.types.index')
                ->with('success', 'Question type deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete question type', [
                'type_id' => $type->id,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete question type'
                ], 500);
            }

            return back()->with('error', 'Failed to delete question type');
        }
    }

    /**
     * Bulk update question types
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'type_ids' => 'required|array',
            'type_ids.*' => 'exists:types,id',
            'action' => 'required|in:activate,deactivate,delete'
        ]);

        DB::beginTransaction();
        try {
            $types = Type::whereIn('id', $validated['type_ids']);

            switch ($validated['action']) {
                case 'activate':
                    $types->update(['is_active' => true]);
                    $message = 'Types activated successfully';
                    break;

                case 'deactivate':
                    $types->update(['is_active' => false]);
                    $message = 'Types deactivated successfully';
                    break;

                case 'delete':
                    // Check if any types are in use
                    $inUse = Question::whereIn('type_id', $validated['type_ids'])
                        ->exists();
                    
                    if ($inUse) {
                        throw new \Exception('Some types are in use and cannot be deleted');
                    }
                    
                    $types->delete();
                    $message = 'Types deleted successfully';
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get types for dropdown/select options
     */
    public function getSelectOptions()
    {
        $types = Type::where('is_active', true)
            ->orderBy('type')
            ->get(['id', 'type', 'input_type']);

        return response()->json($types);
    }
}