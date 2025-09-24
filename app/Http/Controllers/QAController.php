<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QaIssue;
use App\Models\Skill;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QAController extends Controller
{
    /**
     * QA Dashboard
     */
    public function index(Request $request)
    {
        $query = Question::with('skill')
        ->withCount('qaIssues')
        ->withCount(['qaIssues as open_qa_issues_count' => function ($q) {
            $q->where('status', 'open');
        }]);

        // ---- Filters ----
        if ($request->filled('status')) {
            $query->where('qa_status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type_id', (int) $request->type);
        }

        // accept skill or skill_id
        $skillId = $request->input('skill_id', $request->input('skill'));
        if ($skillId) {
            $query->where('skill_id', (int) $skillId);
        }

        // reviewer filter
        if ($request->filled('reviewer')) {
            if ($request->reviewer === 'me') {
                $query->where('qa_reviewer_id', Auth::id());
            } elseif ($request->reviewer === 'unassigned') {
                $query->whereNull('qa_reviewer_id');
            }
        }

        // Level filter via question->skill->tracks(level_id)
        if ($request->filled('level')) {
            $levelId = (int) $request->level;
            $query->whereHas('skill.tracks', function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            });
        }

        // sorting (guard allowed columns)
        $sort = $request->input('sort', 'created_at');
        $allowedSorts = ['created_at', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';

        $questions = $query->orderBy($sort, 'desc')
        ->paginate(25)
        ->appends($request->query());

        // stats
        $stats = [
            'pending'        => Question::where('qa_status', 'unreviewed')->count(),
            'flagged'        => Question::where('qa_status', 'flagged')->count(),
            'needs_revision' => Question::where('qa_status', 'needs_revision')->count(),
            'approved'       => Question::where('qa_status', 'approved')->whereDate('reviewed_at', today())->count(),
        ];

        $skills  = Skill::orderBy('skill')->get();
        $levels  = Level::orderBy('level', 'asc')->get(); // works even if name is present/absent

        return view('admin.qa.index', compact('questions', 'stats', 'skills', 'levels'));
    }

    /**
     * Optional "show" alias -> review page
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
            ]);

            $qaIssues       = $question->qaIssues;
            $reviewHistory  = $this->getReviewHistory($question->id);

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
     * Approve a question
     */
    public function approveQuestion(Question $question)
    {
        $question->update([
            'qa_status'       => 'approved',
            'reviewed_by'     => Auth::id(),
            'reviewed_at'     => now(),
            'qa_reviewer_id'  => Auth::id(),
            'qa_reviewed_at'  => now(),
        ]);

        $this->logReviewAction($question->id, 'approve', 'Approved by reviewer');

        return response()->json(['success' => true, 'message' => 'Question approved!']);
    }

    /**
     * Flag a question
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

        $question->update([
            'qa_status'       => 'flagged',
            'reviewed_by'     => Auth::id(),
            'reviewed_at'     => now(),
            'qa_reviewer_id'  => Auth::id(),
            'qa_reviewed_at'  => now(),
        ]);

        $this->logReviewAction($question->id, 'flag', 'Issue: '.$request->issue_type.' — '.$request->description);

        return response()->json(['success' => true, 'message' => 'Issue reported successfully']);
    }

    /**
     * Resolve a QA issue
     */
    public function resolveIssue(Request $request, QaIssue $issue)
    {
        try {
            DB::beginTransaction();

            $issue->update([
                'status'      => 'resolved',
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
            ]);

            $question   = $issue->question;
            $openIssues = $question->qaIssues()->where('status', 'open')->count();

            if ($openIssues === 0) {
                $question->update(['qa_status' => 'needs_revision']);
            }

            $this->logReviewAction($question->id, 'resolve', 'Issue resolved: '.$issue->issue_type);

            DB::commit();
            return back()->with('success', 'Issue marked as resolved.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QA Resolve Issue Error: '.$e->getMessage());
            return back()->with('error', 'Failed to resolve issue. Please try again.');
        }
    }

    /**
     * Bulk approve
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

                $q->update([
                    'qa_status'       => 'approved',
                    'reviewed_by'     => Auth::id(),
                    'reviewed_at'     => now(),
                    'qa_reviewer_id'  => Auth::id(),
                    'qa_reviewed_at'  => now(),
                ]);

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
     * Bulk flag
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

                Question::where('id', $id)->update([
                    'qa_status'       => 'flagged',
                    'reviewed_by'     => Auth::id(),
                    'reviewed_at'     => now(),
                    'qa_reviewer_id'  => Auth::id(),
                    'qa_reviewed_at'  => now(),
                ]);

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
     * Export CSV
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

    private function getReviewHistory($questionId)
    {
        try {
            return DB::table('review_history')
            ->where('question_id', $questionId)
            ->orderBy('created_at', 'desc')
            ->get();
        } catch (\Throwable $e) {
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
    public function setStatus(Request $request, Question $question)
    {
        $request->validate([
            'status'     => 'required|in:unreviewed,approved,flagged,needs_revision,ai_generated',
            'note'       => 'nullable|string',
            'issue_type' => 'nullable|string|max:50',
        ]);

        $status = $request->status;

    // If flagged and note/issue_type provided, create a QA issue
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

    // Append note (one-line, time-stamped) to qa_notes if provided
        if ($request->filled('note')) {
            $prefix = '['.now()->format('Y-m-d H:i').'] '.(Auth::user()->name ?? 'Reviewer').': ';
            $question->qa_notes = trim(($question->qa_notes ? $question->qa_notes."\n" : '').$prefix.$request->note.($request->issue_type ? " (type: {$request->issue_type})" : ''));
        }

    // Update status + reviewer stamps
        $question->qa_status      = $status;
        $question->qa_reviewer_id = Auth::id();
        $question->qa_reviewed_at = now();

    // Keep legacy fields in sync when approved
        if ($status === 'approved') {
            $question->reviewed_by = Auth::id();
            $question->reviewed_at = now();
        }

        $question->save();

    // Audit trail
        $this->logReviewAction($question->id, 'status', "Set status to {$status}");

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }
    public function saveNotes(Request $request, Question $question)
    {
        $data = $request->validate([
        'notes'  => 'nullable|string',     // empty string clears notes
        'append' => 'sometimes|boolean',   // if true, append instead of replace
    ]);

    // Replace vs append
        if (!empty($data['append'])) {
            if (strlen(trim((string)($data['notes'] ?? ''))) > 0) {
                $prefix = '['.now()->format('Y-m-d H:i').'] '.(Auth::user()->name ?? 'Reviewer').': ';
                $line   = $prefix.$data['notes'];
                $question->qa_notes = trim(($question->qa_notes ? $question->qa_notes."\n" : '').$line);
            }
        } else {
        // Replace entire notes (allows clearing)
            $question->qa_notes = (string)($data['notes'] ?? '');
        }

    // Ensure reviewer stamps are set
        $question->qa_reviewer_id = $question->qa_reviewer_id ?: Auth::id();
        $question->qa_reviewed_at = $question->qa_reviewed_at ?: now();
        $question->save();

    // Audit trail (safe no-op if table missing)
        $this->logReviewAction($question->id, 'notes', !empty($data['append']) ? 'Appended notes' : 'Replaced notes');

        return response()->json(['success' => true, 'message' => 'Notes saved.']);
    }

}
