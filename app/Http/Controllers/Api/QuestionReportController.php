<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\QuestionReport;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class QuestionReportController extends Controller
{
    /**
     * Display all reports
     */
    public function index(Request $request)
    {
    	$query = QuestionReport::with(['question', 'user', 'reviewer']);

        // Filter by status
    	if ($request->has('status') && $request->status !== 'all') {
    		$query->where('status', $request->status);
    	}

        // Filter by report type
    	if ($request->has('report_type') && $request->report_type !== 'all') {
    		$query->where('report_type', $request->report_type);
    	}

        // Search by question ID
    	if ($request->has('question_id')) {
    		$query->where('question_id', $request->question_id);
    	}

    	$reports = $query->orderBy('created_at', 'desc')->paginate(50);

    	return view('admin.reports.index', compact('reports'));
    }

    /**
     * Show reports for a specific question
     */
    public function show(Question $question)
    {
    	$reports = $question->reports()
    	->with(['user', 'reviewer'])
    	->orderBy('created_at', 'desc')
    	->get();

    	return view('admin.reports.show', compact('question', 'reports'));
    }

    /**
     * Update report status
     */
    public function updateStatus(Request $request, QuestionReport $report)
    {
    	$request->validate([
    		'status' => 'required|in:pending,under_review,resolved,dismissed',
    		'admin_notes' => 'nullable|string|max:1000',
    	]);

    	$report->update([
    		'status' => $request->status,
    		'reviewed_by' => auth()->id(),
    		'reviewed_at' => now(),
    		'admin_notes' => $request->admin_notes,
    	]);

    	return response()->json([
    		'success' => true,
    		'message' => 'Report status updated successfully',
    		'report' => $report->fresh(['reviewer'])
    	]);
    }
    public function store(Request $request, $question)
    {
    // Try to get the user the same way as your /me route
    	$user = Auth::guard('sanctum')->user() ?? $request->user();

    	if (!$user) {
    		return response()->json([
    			'success' => false,
    			'message' => 'Unauthenticated.'
    		], 401);
    	}
    // Validate the incoming request
    	$validated = $request->validate([
    		'report_type' => 'required|string|in:unclear_question,wrong_answer,inappropriate_content,other',
    		'comment' => 'nullable|string|max:1000',
    	]);

    // Find the question or return 404
    	$question = Question::find($question);

    	if (!$question) {
    		return response()->json([
    			'success' => false,
    			'message' => 'Question not found.'
    		], 404);
    	}

    // Check for recent reports from this user (within 24 hours)
    	$recentReport = QuestionReport::where('question_id', $question->id)
        ->where('user_id', $user->id)  // Use $user instead of $request->user()
        ->where('created_at', '>=', now()->subHours(24))
        ->first();
        
        if ($recentReport) {
        	return response()->json([
        		'success' => false,
        		'message' => 'You have already reported this question recently.'
        	], 429);
        }

    // Create the report
        $report = QuestionReport::create([
        	'question_id' => $question->id,
        'user_id' => $user->id,  // Use $user instead of $request->user()
        'report_type' => $validated['report_type'],
        'comment' => $validated['comment'] ?? null,
        'status' => 'pending'
    ]);

        return response()->json([
        	'success' => true,
        	'message' => 'Question reported successfully.',
        	'data' => $report
        ], 200);
    }
}