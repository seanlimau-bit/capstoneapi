<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Skill;
use App\Models\Level;
use App\Models\QAIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LookupOptionsService;
use Illuminate\Support\Facades\Schema;

class QAController extends Controller
{
    /**
     * QA Dashboard
     */
    public function index(Request $request, LookupOptionsService $lookup)
    {
        // Base builder (unchanged)
        $query = Question::query()
        ->with(['skill'])
        ->withCount('qaIssues')
        ->withCount([
            'qaIssues as open_qa_issues_count' => function ($q) {
                $q->where('status', 'open');
            },
        ])
            // only questions whose Skill is public
        ->whereHas('skill', fn($s) => $s->public());

        // Approved Today window based on app TZ
        $tz    = config('app.timezone', 'UTC');
        $start = now($tz)->startOfDay()->utc();
        $end   = now($tz)->endOfDay()->utc();

        if ($request->boolean('today')) {
            $query->where('qa_status', 'approved')
            ->whereBetween(DB::raw('COALESCE(reviewed_at, updated_at)'), [$start, $end]);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('qa_status', $request->string('status'));
        }
        if ($request->filled('type')) {
            $query->where('type_id', (int) $request->type);
        }
        if ($skillId = $request->input('skill_id', $request->input('skill'))) {
            $query->where('skill_id', (int) $skillId);
        }
        if ($request->filled('reviewer')) {
            if ($request->reviewer === 'me') {
                $query->where('qa_reviewer_id', Auth::id());
            } elseif ($request->reviewer === 'unassigned') {
                $query->whereNull('qa_reviewer_id');
            }
        }
        if ($request->filled('level')) {
            $levelId = (int) $request->level;
            $query->whereHas('skill.tracks', fn($q) => $q->where('level_id', $levelId));
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $allowedSorts = ['created_at', 'updated_at'];
        if (Schema::hasColumn('questions', 'priority')) {
            $allowedSorts[] = 'priority';
        }
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $questions = $query->orderBy($sort, 'desc')
        ->paginate(25)
        ->appends($request->query());

        // Stats (mirror the public-skill constraint)
        $statsBase = Question::query()->whereHas('skill', fn($s) => $s->public());
        $todayExpr = DB::raw('COALESCE(reviewed_at, updated_at)');

        $stats = [
            'pending'        => (clone $statsBase)->where('qa_status', 'unreviewed')->count(),
            'flagged'        => (clone $statsBase)->where('qa_status', 'flagged')->count(),
            'needs_revision' => (clone $statsBase)->where('qa_status', 'needs_revision')->count(),
            'approved'       => (clone $statsBase)->where('qa_status', 'approved')
            ->whereBetween($todayExpr, [$start, $end])
            ->count(),
        ];

        // Filter options
        $opts    = $lookup->filterOptionsForQuestionsIndex();
        $skills  = $opts['skills'];
        $levels  = Level::orderBy('level', 'asc')->get();

        return view('admin.qa.index', compact('questions', 'stats', 'skills', 'levels'));
    }

    /**
     * Alias -> review page
     */
    public function show(Question $question)
    {
        return $this->reviewQuestion($question);
    }

    /**
     * Question review page
     */
    public function reviewQuestion(Question $question)
    {
        try {
            $question->load([
                'skill.tracks.level',
                'qaIssues' => fn($q) => $q->with('reviewer')->latest(),
                'hints',
                'solutions',
                'difficulty',
                'type',
            ]);

            $qaIssues      = $question->qaIssues;
            $reviewHistory = $this->getReviewHistory($question->id);

            $nextQuestion = Question::where('id', '>', $question->id)
            ->where('qa_status', '!=', 'approved')
            ->orderBy('id')
            ->first();

            $previousQuestion = Question::where('id', '<', $question->id)
            ->where('qa_status', '!=', 'approved')
            ->orderBy('id', 'desc')
            ->first();

            return view('admin.qa.show', compact(
                'question',
                'qaIssues',
                'reviewHistory',
                'nextQuestion',
                'previousQuestion'
            ));
        } catch (\Throwable $e) {
            Log::error('QA Review Error: '.$e->getMessage());
            return back()->with('error', 'Failed to load question review. Please try again.');
        }
    }

    /**
     * Next helper used by the "Next" button
     * GET /admin/qa/next?after={id}&status={optional}
     */
    public function next(Request $request)
    {
        $afterId = (int) $request->integer('after', 0);
        $userId  = auth()->id();

    // Find the next *unreviewed* question
        $q = Question::query()
        ->when($afterId > 0, fn ($qq) => $qq->where('id', '>', $afterId))
        ->where('qa_status', 'unreviewed')
        ->orderBy('id', 'asc');

        $next = $q->first();

    // If no questions found after current ID, try from the beginning
        if (!$next && $afterId > 0) {
            $next = Question::where('qa_status', 'unreviewed')
            ->orderBy('id', 'asc')
            ->first();
        }

        if (!$next) {
            return redirect()
            ->route('admin.qa.index', ['status' => 'unreviewed'])
            ->with('success', 'No unreviewed questions available.');
        }

    // OPTIONAL: soft-claim if currently unassigned (reduces race conditions)
        if (is_null($next->qa_reviewer_id)) {
        // Only claim if still unassigned *right now*
            $updated = Question::where('id', $next->id)
            ->whereNull('qa_reviewer_id')
            ->update(['qa_reviewer_id' => $userId]);

            if ($updated) {
            // Touch history (optional)
                $this->logReviewAction($next->id, 'assign', 'Auto-assigned via Next');
            }
        }

        return redirect()->route('admin.qa.questions.review', $next->id);
    }


    public function previous(Request $request)
    {
        $beforeId = (int) $request->integer('before', PHP_INT_MAX);
        $userId   = auth()->id();
        
    // Find the previous question that THIS USER has reviewed
        $q = Question::query()
        ->when($beforeId < PHP_INT_MAX, fn ($qq) => $qq->where('id', '<', $beforeId))
        ->where('qa_reviewer_id', $userId)  // Only questions reviewed by current user
        ->whereIn('qa_status', ['approved', 'flagged', 'needs_revision', 'ai_generated'])  // Already reviewed
        ->orderBy('id', 'desc');
        
        $prev = $q->first();
        
    // If no questions found before current ID, wrap to the end
        if (!$prev && $beforeId < PHP_INT_MAX) {
            $prev = Question::where('qa_reviewer_id', $userId)
            ->whereIn('qa_status', ['approved', 'flagged', 'needs_revision', 'ai_generated'])
            ->orderBy('id', 'desc')
            ->first();
        }
        
        if (!$prev) {
            return redirect()
            ->route('admin.qa.index')
            ->with('info', 'No previous reviewed questions found.');
        }
        
        return redirect()->route('admin.qa.questions.review', $prev->id);
    }
    /**
     * Approve => Public
     */
    public function approveQuestion(Question $question)
    {
        $user = Auth::user();

        // --- Permission checks ---------------------------------------------------
        // Must be QA with approval powers (either p2/p3 or any) or an admin.
        $canApproveAny = method_exists($user, 'hasPermission') ? $user->hasPermission('qa_approve_any') : false;
        $canApproveP23 = method_exists($user, 'hasPermission') ? $user->hasPermission('qa_approve_p2p3') : false;

        if (!($user->canAccessAdmin() || $canApproveAny || $canApproveP23)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve.'
            ], 403);
        }

        // Prevent self-approval if the reviewer was the last QA editor (optional column)
        if (\Schema::hasColumn('questions', 'last_qa_editor_id')
            && $question->last_qa_editor_id
            && $question->last_qa_editor_id === $user->id
            && !$user->canAccessAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Another reviewer must approve your own QA edit.'
            ], 403);
    }

        // Block approval while there are open QA issues (consistent with bulkApprove)
    $openIssues = $question->qaIssues()->where('status', 'open')->count();
    if ($openIssues > 0) {
        return response()->json([
            'success' => false,
            'message' => "Cannot approve: {$openIssues} open issue(s) remain."
        ], 422);
    }

        // --- Approve & publish ---------------------------------------------------
        // Approve in QA, set public visibility, stamp reviewer times.
    $question->qa_status       = 'approved';
    $question->reviewed_by     = $user->id;
    $question->reviewed_at     = now();
    $question->qa_reviewer_id  = $user->id;
    $question->qa_reviewed_at  = now();
        $question->status_id       = 3; // Public

        if (\Schema::hasColumn('questions', 'published_at')) {
            // Ensure your Question model casts published_at as datetime
            $question->published_at = now();
        }

        // Clear last_qa_editor_id once approved (optional hygiene)
        if (\Schema::hasColumn('questions', 'last_qa_editor_id')) {
            $question->last_qa_editor_id = null;
        }

        $question->save();

        // Audit trail (safe no-op if table missing)
        $this->logReviewAction($question->id, 'approve', 'Approved by reviewer');

        return response()->json([
            'success' => true,
            'message' => 'Question approved!'
        ]);
    }


    /**
     * Flag => Draft
     */
    public function flagQuestion(Request $request, Question $question)
    {
        $request->validate([
            'issue_type'  => 'required|string',
            'description' => 'required|string|min:10',
        ]);

        QaIssue::create([
            'question_id'   => $question->id,
            'reviewer_id'   => Auth::id(),
            'reviewer_name' => Auth::user()->name,
            'issue_type'    => $request->issue_type,
            'description'   => $request->description,
            'status'        => 'open',
        ]);

        $question->qa_status       = 'flagged';
        $question->reviewed_by     = Auth::id();
        $question->reviewed_at     = now();
        $question->qa_reviewer_id  = Auth::id();
        $question->qa_reviewed_at  = now();
        $question->status_id       = 4; // Draft
        if (Schema::hasColumn('questions', 'published_at')) {
            $question->published_at = null;
        }
        $question->save();

        $this->logReviewAction($question->id, 'flag', 'Issue: '.$request->issue_type.' — '.$request->description);

        return response()->json(['success' => true, 'message' => 'Issue reported successfully']);
    }

    /**
     * Resolve an issue
     */
    public function issueStatus(\App\Models\QaIssue $issue, \Illuminate\Http\Request $request)
    {
        $request->validate(['status' => 'required|in:open,resolved,dismissed']);

        $issue->status = $request->string('status');
        $issue->save();

        return response()->json([
            'success' => true,
            'message' => 'Issue status updated.',
            'status'  => $issue->status,
        ]);
    }


    /**
     * Bulk approve => Public
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'question_ids'   => 'required|array|min:1',
            'question_ids.*' => 'exists:questions,id',
        ]);

        try {
            DB::beginTransaction();

            $approved = 0;
            $skipped  = 0;

            foreach ($request->question_ids as $id) {
                $q = Question::find($id);
                if (!$q) continue;

                $open = $q->qaIssues()->where('status', 'open')->count();
                if ($open > 0) { $skipped++; continue; }

                $q->qa_status       = 'approved';
                $q->reviewed_by     = Auth::id();
                $q->reviewed_at     = now();
                $q->qa_reviewer_id  = Auth::id();
                $q->qa_reviewed_at  = now();
                $q->status_id       = 3;
                if (Schema::hasColumn('questions', 'published_at')) {
                    $q->published_at = now();
                }
                $q->save();

                $this->logReviewAction($id, 'approve', 'Bulk approved by QA reviewer');
                $approved++;
            }

            DB::commit();

            $msg = "Approved {$approved} questions.";
            if ($skipped > 0) $msg .= " Skipped {$skipped} with open issues.";

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QA Bulk Approve Error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to bulk approve questions. Please try again.']);
        }
    }

    /**
     * Bulk flag => Draft
     */
    public function bulkFlag(Request $request)
    {
        $request->validate([
            'question_ids'   => 'required|array|min:1',
            'question_ids.*' => 'exists:questions,id',
            'issue_type'     => 'required|string',
            'description'    => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->question_ids as $id) {
                QaIssue::create([
                    'question_id'   => $id,
                    'reviewer_id'   => Auth::id(),
                    'reviewer_name' => Auth::user()->name,
                    'issue_type'    => $request->issue_type,
                    'description'   => $request->description,
                    'status'        => 'open',
                ]);

                $q = Question::find($id);
                if (!$q) continue;

                $q->qa_status       = 'flagged';
                $q->reviewed_by     = Auth::id();
                $q->reviewed_at     = now();
                $q->qa_reviewer_id  = Auth::id();
                $q->qa_reviewed_at  = now();
                $q->status_id       = 4;
                if (Schema::hasColumn('questions', 'published_at')) {
                    $q->published_at = null;
                }
                $q->save();

                $this->logReviewAction($id, 'flag', 'Bulk flagged: '.$request->description);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Flagged questions for review.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QA Bulk Flag Error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to bulk flag questions. Please try again.']);
        }
    }

    /**
     * Export CSV (unchanged contract)
     */
    public function export(Request $request)
    {
        try {
            $questions = Question::with(['skill', 'qaIssues'])->withCount('qaIssues')->get();

            $rows = [];
            $rows[] = ['ID','Question','Type','Status','Skill','Issues Count','Created At','Reviewed At'];
            foreach ($questions as $q) {
                $rows[] = [
                    $q->id,
                    strip_tags(substr($q->question ?? '', 0, 100)),
                    $q->type_id == 1 ? 'Multiple Choice' : 'Fill in Blank',
                    $q->qa_status ?? 'unreviewed',
                    $q->skill->skill ?? 'No Skill',
                    $q->qa_issues_count,
                    optional($q->created_at)->format('Y-m-d H:i:s'),
                    optional($q->reviewed_at)->format('Y-m-d H:i:s') ?: 'Not reviewed',
                ];
            }

            $filename = 'qa_report_'.date('Y-m-d_H-i-s').'.csv';
            $headers  = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($rows) {
                $f = fopen('php://output', 'w');
                foreach ($rows as $r) fputcsv($f, $r);
                fclose($f);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('QA Export Error: '.$e->getMessage());
            return back()->with('error', 'Failed to export QA report. Please try again.');
        }
    }

    // -------- helpers --------

    private function getReviewHistory(int $questionId)
    {
        try {
            // You can later swap this for a true qa_history table if you add one
            return \App\Models\QaIssue::with('reviewer')
                ->where('question_id', $questionId)
                ->latest()
                ->take(50)
                ->get();
        } catch (\Throwable $e) {
            \Log::warning('getReviewHistory fallback: '.$e->getMessage());
            return collect();
        }
    }

    private function logReviewAction($questionId, $action, $comment = null)
    {
        try {
            DB::table('review_history')->insert([
                'question_id'   => $questionId,
                'reviewer_id'   => Auth::id(),
                'reviewer_name' => Auth::user()->name ?? 'Reviewer',
                'action'        => $action,
                'comment'       => $comment,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::info('Review history logging skipped - table may not exist');
        }
    }

    /**
     * Generic status setter (keeps blades/api intact)
     * Enforces: approved => Public, otherwise => Draft
     */
    public function setStatus(Request $request, Question $question)
    {
        $request->validate([
            'status'     => 'required|in:unreviewed,approved,flagged,needs_revision,ai_generated',
            'note'       => 'nullable|string',
            'issue_type' => 'nullable|string|max:50',
        ]);

        $status = $request->status;

        // Auto-create issue when flagging with note/type
        if ($status === 'flagged' && ($request->filled('note') || $request->filled('issue_type'))) {
            try {
                QaIssue::create([
                    'question_id'   => $question->id,
                    'reviewer_id'   => Auth::id(),
                    'reviewer_name' => Auth::user()->name ?? 'Reviewer',
                    'issue_type'    => $request->input('issue_type', 'other'),
                    'description'   => $request->input('note', 'Flagged'),
                    'status'        => 'open',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to auto-create QA issue: '.$e->getMessage());
            }
        }

        // Append note
        if ($request->filled('note')) {
            $prefix = '['.now()->format('Y-m-d H:i').'] '.(Auth::user()->name ?? 'Reviewer').': ';
            $question->qa_notes = trim(
                ($question->qa_notes ? $question->qa_notes."\n" : '')
                .$prefix.$request->note
                .($request->issue_type ? " (type: {$request->issue_type})" : '')
            );
        }

        // Update stamps + status
        $question->qa_status      = $status;
        $question->qa_reviewer_id = Auth::id();
        $question->qa_reviewed_at = now();

        if ($status === 'approved') {
            // legacy sync
            $question->reviewed_by = Auth::id();
            $question->reviewed_at = now();
            // publish
            $question->status_id = 3;
            if (Schema::hasColumn('questions', 'published_at')) {
                $question->published_at = now();
            }
        } else {
            // revert visibility
            $question->status_id = 4;
            if (Schema::hasColumn('questions', 'published_at')) {
                $question->published_at = null;
            }
        }

        $question->save();

        $this->logReviewAction($question->id, 'status', "Set status to {$status}");

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }

    /**
     * Notes endpoint (unchanged contract)
     */
    public function saveNotes(Request $request, Question $question)
    {
        $data = $request->validate([
            'notes'  => 'nullable|string',
            'append' => 'sometimes|boolean',
        ]);

        if (!empty($data['append'])) {
            if (strlen(trim((string)($data['notes'] ?? ''))) > 0) {
                $prefix = '['.now()->format('Y-m-d H:i').'] '.(Auth::user()->name ?? 'Reviewer').': ';
                $line   = $prefix.$data['notes'];
                $question->qa_notes = trim(($question->qa_notes ? $question->qa_notes."\n" : '').$line);
            }
        } else {
            $question->qa_notes = (string)($data['notes'] ?? '');
        }

        $question->qa_reviewer_id = $question->qa_reviewer_id ?: Auth::id();
        $question->qa_reviewed_at = $question->qa_reviewed_at ?: now();
        $question->save();

        $this->logReviewAction($question->id, 'notes', !empty($data['append']) ? 'Appended notes' : 'Replaced notes');

        return response()->json(['success' => true, 'message' => 'Notes saved.']);
    }
}
