<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Track;
use App\Models\Field;
use App\Models\Level;
use App\Models\User;
use App\Models\Status;
use App\Models\Difficulty;
use App\Models\Type;
use App\Services\QuestionGenerationService;
use App\Services\LookupOptionsService;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionGenerationService $questionService,
        private LookupOptionsService $opts
    ) {}


    // =====================================================
    // GLOBAL QUESTION MANAGEMENT
    // =====================================================
    public function index(Request $request)
    {
    // Base query + eager loads
        $query = Question::with(['skill.tracks.level', 'difficulty', 'type'])
        ->orderBy('created_at', 'desc');

    // ---------- Filters (apply BEFORE cloning for totals/pagination) ----------
        if ($request->filled('skill_id')) {
            $query->where('skill_id', $request->input('skill_id'));
        }

        if ($request->filled('qa_status')) {
            $qa = strtolower(trim($request->input('qa_status')));
            $valid = Question::getQaStatuses(); // reads ENUM from DB
            if (in_array($qa, $valid, true)) {
                $query->where('qa_status', $qa);
            }
        }

        if ($request->filled('source')) {
            $query->where('source', 'like', '%' . $request->input('source') . '%');
        }

        if ($request->filled('difficulty_id')) {
            $query->where('difficulty_id', $request->input('difficulty_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->input('type_id'));
        }

        // Field filter via skill->tracks->field
        if ($request->filled('field_id')) {
            $query->whereHas('skill.tracks', function ($tq) use ($request) {
                $tq->whereHas('field', function ($fq) use ($request) {
                    $fq->where('id', $request->input('field_id'));
                });
            });
        }

        // Free text search
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('question', 'like', $term)
                ->orWhere('explanation', 'like', $term)
                ->orWhere('source', 'like', $term)
                ->orWhereHas('skill', fn ($sq) => $sq->where('skill', 'like', $term));
            });
        }

        // ---------- Totals & pagination (clone AFTER filters) ----------
        $base = clone $query;

        $questions = $query->paginate(50)->withQueryString();

        $totals = [
            'total'    => (clone $base)->count(),
            'approved' => (clone $base)->where('qa_status', 'approved')->count(),
            'pending'  => (clone $base)->where('qa_status', 'unreviewed')->count(),
            'flagged'  => (clone $base)->where('qa_status', 'flagged')->count(),
        ];

        $filterOptions = $this->opts->filterOptionsForQuestionsIndex();

        // ---------- AJAX ----------
        if ($request->ajax() || $request->wantsJson()) {
            $html = view('admin.questions.table-body', [
                'questions'    => $questions->items(),
                'skillId'      => $request->input('skill_id'),
                'withCheckbox' => true,
            ])->render();

        return response()->json([
            'html'           => $html,
            'num_pages'      => $questions->lastPage(),
            'current_page'   => $questions->currentPage(),
            'per_page'       => $questions->perPage(),
            'total'          => $questions->total(),
            'totals'         => $totals,
            'filter_options' => $filterOptions,
        ]);
    }

    // ---------- Blade ----------
    $skill = $request->filled('skill_id') ? Skill::find($request->input('skill_id')) : null;

    return view('admin.questions.index', compact('questions', 'skill', 'totals', 'filterOptions'));
}

public function search(Request $request)
{
    $query = Question::with(['skill.tracks.level', 'difficulty', 'type', 'author']);

    if ($request->skill) {
        $query->where('skill_id', $request->skill);
    }

    if ($request->level) {
        $query->whereHas('skill.tracks', function ($q) use ($request) {
            $q->where('level_id', $request->level);
        });
    }

    if ($request->keyword) {
        $query->where('question', 'LIKE', '%' . $request->keyword . '%');
    }

    $questions = $query->paginate(20);

    return response()->json([
        'questions' => $questions->items(),
        'pagination' => [
            'next_page_url' => $questions->nextPageUrl(),
            'prev_page_url' => $questions->previousPageUrl(),
            'current_page' => $questions->currentPage(),
            'last_page' => $questions->lastPage()
        ]
    ]);
}

public function create()
{
    $data = $this->getFormData();

    if (request()->expectsJson()) {
        return response()->json($data);
    }

    return view('admin.questions.create', $data);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'skill_id' => 'required|exists:skills,id',
            'difficulty_id' => 'required|exists:difficulties,id',
            'type_id' => 'required|exists:types,id',
            'question' => 'required|string|max:2000',
            'answer0' => 'required|string|max:255',
            'answer1' => 'nullable|string|max:255',
            'answer2' => 'nullable|string|max:255',
            'answer3' => 'nullable|string|max:255',
            'correct_answer' => 'nullable|integer|between:0,3',
            'explanation' => 'nullable|string|max:1000',
            'calculator' => 'nullable|in:scientific,basic',
        ]);

        DB::beginTransaction();
        try {
            $validated['user_id'] = auth()->id();
            $validated['status_id'] = 3; // Active
            $validated['qa_status'] = 'unreviewed';

            $question = Question::create($validated);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question created successfully',
                    'question' => $question->load(['skill', 'difficulty', 'type'])
                ], 201);
            }

            return redirect()->route('admin.questions.index')
            ->with('success', 'Question created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Question creation failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create question: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create question');
        }
    }

    public function show(Question $question, LookupOptionsService $opts)
    {
        $question->load(['skill.tracks.field','difficulty','type','status','author','hints','solutions.author']);

        $dropdowns = $opts->questionShowOptions();

        $data = array_merge(['question' => $question], $dropdowns);

        if (request()->expectsJson()) {
            return response()->json($data);
        }

        return view('admin.questions.show', $data);
    }

    public function preview(Question $question)
    {
        return view('admin.questions.preview', compact('question'));
    }
    /**
     * Update a specific field of a question (for inline editing)
     */
    public function updateField(Request $request, Question $question)
    {
        try {
            $u = auth()->user();
            $isAuthor = $u && $question->user_id === $u->id;
            $isAdmin  = $u && $u->canAccessAdmin();
            $isQAEdit = $u && $u->isQAEditor();

            $field = $request->input('field');
            $value = $request->input('value');

            $validationRules = [
                'skill_id'        => 'nullable|exists:skills,id',
                'difficulty_id'   => 'nullable|exists:difficulties,id',
                'type_id'         => 'nullable|exists:types,id',
                'status_id'       => 'nullable|exists:statuses,id',
                'qa_status'       => 'required|in:unreviewed,approved,flagged,needs_revision,ai_generated',
                'question'        => 'required|string|max:1000',
                'answer0'         => 'nullable|string|max:255',
                'answer1'         => 'nullable|string|max:255',
                'answer2'         => 'nullable|string|max:255',
                'answer3'         => 'nullable|string|max:255',
                'correct_answer'  => 'nullable|integer',
                'explanation'     => 'nullable|string|max:2000',
                'hint'            => 'nullable|string|max:500',
                'calculator'      => 'nullable|string|max:255',
                'hint_text'       => 'nullable|string|max:500',
            ];

            if (!array_key_exists($field, $validationRules)) {
                return response()->json(['success'=>false,'message'=>'Field not allowed for inline editing'], 400);
            }

                        // Permissions:
                        // 1) Only QA (any) can change qa_status (your QAController handles status normally; inline is rare)
            if ($field === 'qa_status' && !($u && $u->canAccessQA())) {
                abort(403);
            }

                        // 2) For content fields, require author/admin/QA Editor
            $contentFields = [
                'question','explanation','answer0','answer1','answer2','answer3',
                'correct_answer','skill_id','difficulty_id','type_id','calculator','hint','hint_text'
            ];
            if (in_array($field, $contentFields, true)) {
                abort_unless($isAuthor || $isAdmin || $isQAEdit, 403);
            }

                        // Validate value
            $validator = \Validator::make([$field => $value], [$field => $validationRules[$field]]);
            if ($validator->fails()) {
                return response()->json(['success'=>false,'message'=>$validator->errors()->first($field)], 422);
            }

                        // Detect meaningful change for unpublish/return-to-QA rules
            $old = $question->getAttribute($field);

            $question->setAttribute($field, $value);
            $question->save();

            $meaningfulContentField = in_array($field, $contentFields, true) && $old !== $value;

                        // If approved content changed by anyone → unpublish & return to QA
            if ($meaningfulContentField && $question->qa_status === 'approved') {
                $question->update([
                    'qa_status'       => 'unreviewed',
                    'status_id'       => 4,
                    'published_at'    => null,
                    'qa_reviewer_id'  => null,
                    'qa_reviewed_at'  => null,
                ]);
            }

                        // If QA Editor changed content → always return to QA & unpublish
            if ($meaningfulContentField && $isQAEdit && !$isAdmin) {
                $question->update([
                    'qa_status'       => 'unreviewed',
                    'status_id'       => 4,
                    'published_at'    => null,
                    'qa_reviewer_id'  => null,
                    'qa_reviewed_at'  => null,
                    'last_qa_editor_id' => \Schema::hasColumn('questions','last_qa_editor_id') ? $u->id : $question->last_qa_editor_id,
                ]);
                try {
                    DB::table('review_history')->insert([
                        'question_id'   => $question->id,
                        'reviewer_id'   => $u->id,
                        'reviewer_name' => $u->name ?? 'QA Editor',
                        'action'        => 'qa_edit',
                        'comment'       => 'Inline edit; auto-resubmitted',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            return response()->json(['success'=>true,'message'=>'Field updated successfully','field'=>$field,'value'=>$value]);

        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Update failed'], 500);
        }
    }


    public function update(Request $request, Question $question)
    {
        $u = auth()->user();
        $isAuthor  = $u && $question->user_id === $u->id;
        $isAdmin   = $u && $u->canAccessAdmin();
                    $isQAEdit  = $u && $u->isQAEditor(); // needs 'qa_edit_content' perm

        // Only author, admin, or QA Editor may edit
        abort_unless($isAuthor || $isAdmin || $isQAEdit, 403);

        $validated = $request->validate([
            'skill_id'        => 'required|exists:skills,id',
            'difficulty_id'   => 'required|exists:difficulties,id',
            'type_id'         => 'required|exists:types,id',
            'question'        => 'required|string|max:2000',
            'answer0'         => 'required|string|max:255',
            'answer1'         => 'required|string|max:255',
            'answer2'         => 'required|string|max:255',
            'answer3'         => 'required|string|max:255',
            'correct_answer'  => 'required|integer|between:0,3',
            'explanation'     => 'nullable|string|max:1000',
            'calculator'      => 'nullable|in:scientific,basic',
        ]);

        DB::beginTransaction();
        try {
            // Track old values to detect meaningful changes
            $before = $question->only([
                'question','explanation','question_image',
                'answer0','answer1','answer2','answer3',
                'answer0_image','answer1_image','answer2_image','answer3_image',
                'correct_answer','skill_id','difficulty_id','type_id'
            ]);

            $question->update($validated);

            // If *approved* content was changed by anyone, unpublish & return to QA
            $meaningfulChanged = collect($before)->some(function ($old, $key) use ($question) {
                return $question->getAttribute($key) !== $old;
            });

            if ($meaningfulChanged && $question->qa_status === 'approved') {
                $question->qa_status       = 'unreviewed';
                $question->status_id       = 4;        // Draft
                $question->published_at    = null;     // needs cast in model
                $question->qa_reviewer_id  = null;
                $question->qa_reviewed_at  = null;
                $question->save();
            }

            // If edited by QA Editor (not full admin), always return to QA & unpublish
            if ($isQAEdit && !$isAdmin && $meaningfulChanged) {
                $question->qa_status       = 'unreviewed';
                $question->status_id       = 4;
                $question->published_at    = null;
                $question->qa_reviewer_id  = null;
                $question->qa_reviewed_at  = null;
                // Optional: track who edited so we can block self-approval later
                if (\Schema::hasColumn('questions', 'last_qa_editor_id')) {
                    $question->last_qa_editor_id = $u->id;
                }
                $question->save();

                // Audit trail
                try {
                    DB::table('review_history')->insert([
                        'question_id'   => $question->id,
                        'reviewer_id'   => $u->id,
                        'reviewer_name' => $u->name ?? 'QA Editor',
                        'action'        => 'qa_edit',
                        'comment'       => 'Edited by QA; auto-resubmitted',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                } catch (\Throwable $e) {}
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Question updated successfully',
                    'question' => $question->fresh(['skill','difficulty','type']),
                ]);
            }

            return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'Question updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Question update failed', [
                'question_id' => $question->id,
                'error'       => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update question: '.$e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update question');
        }
    }

    public function destroy(Question $question)
    {
        DB::beginTransaction();
        try {
            $question->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Question deleted successfully'
                ]);
            }

            return redirect()->route('admin.questions.index')
            ->with('success', 'Question deleted successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Question deletion failed', ['question_id' => $question->id, 'error' => $e->getMessage()]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete question: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete question');
        }
    }

    // =====================================================
    // QUESTION GENERATION - MAIN METHOD
    // =====================================================

    public function generateQuestions(Request $request, QuestionGenerationService $svc)
    {
        try {
        // Normalise "count" from various client payloads
            $count = $request->input('question_count', $request->input('count', $request->input('n')));
            $count = (int) ($count ?? 0);
            $source = $request->source;

            // Basic validation for IDs and options (count validated manually above)
            $validated = $request->validate([
                'question_id' => ['nullable', 'integer', 'exists:questions,id'],
                'skill_id' => ['nullable', 'integer', 'exists:skills,id'],
                    ]);

            // Enforce limits
            if ($count < 1 || $count > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'Count must be between 1 and 50.',
                        ], 422);
            }

            // Choose mode: variations (question_id) vs by-skill (skill_id)
            if (!empty($validated['question_id']) || $source = "question") {
                    $question = Question::findOrFail($validated['question_id']);

                // Variations are limited to 20
                $vCount = min($count, 20);

                $generated = $svc->generateVariations($question, $vCount, [
                        'focus_areas' => $validated['focus_areas'] ?? null,
                        'question_types' => 'same', // keep same type
                        ]);

                    return response()->json([
                        'success' => true,
                        'mode' => 'variations',
                        'count_requested' => $count,
                        'count_used' => $vCount,
                            'questions_created' => count($generated),
                        'question_ids' => collect($generated)->pluck('id')->values(),
                    ]);
                }

                if (!empty($validated['skill_id']) || $source = "skill") {
                        $skill = Skill::findOrFail($validated['skill_id']);

                    $generated = $svc->generateForSkill($skill, $count, [
                    'focus_areas' => $validated['focus_areas'] ?? null,
                        ]);

                    return response()->json([
                        'success' => true,
                        'mode' => 'by_skill',
                        'count_requested' => $count,
                        'questions_created' => count($generated),
                        'question_ids' => collect($generated)->pluck('id')->values(),
                    ]);
                }

                // Neither ID provided
                return response()->json([
                    'success' => false,
                    'message' => 'Provide either question_id (for variations) or skill_id (for new questions).',
                ], 422);

            } catch (\Throwable $e) {
                Log::error('Question generation failed', [
                    'error' => $e->getMessage(),
                    ]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
    public function deleteQuestionImage(Question $question)
    {
        try {
            if ($question->question_image) {
                $oldPath = str_replace('/storage/', '', $question->question_image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $question->question_image = null;
                $question->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Question image removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Question image deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadAnswerImage(Request $request, Question $question, $option)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:6144'
        ]);

        if (!in_array($option, ['0', '1', '2', '3'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid answer option'
            ], 400);
        }

        try {
            $image = $request->file('image');
            $fieldName = "answer{$option}_image";
            $filename = time() . '_' . $question->id . "_answer{$option}." . $image->getClientOriginalExtension();
            $path = $image->storeAs('uploads/questions/answers', $filename, 'public');

            // Remove old image if exists
            if ($question->$fieldName) {
                $oldPath = str_replace('/storage/', '', $question->$fieldName);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $question->$fieldName = '/storage/' . $path;
            $question->save();

            return response()->json([
                'success' => true,
                'message' => "Answer option " . ['A', 'B', 'C', 'D'][$option] . " image uploaded successfully",
                'image_url' => asset('storage/' . $path),
                'option' => $option
            ]);

        } catch (\Exception $e) {
            Log::error("Answer {$option} image upload failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteAnswerImage(Question $question, $option)
    {
        if (!in_array($option, ['0', '1', '2', '3'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid answer option'
            ], 400);
        }

        try {
            $fieldName = "answer{$option}_image";

            if ($question->$fieldName) {
                $oldPath = str_replace('/storage/', '', $question->$fieldName);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                $question->$fieldName = null;
                $question->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Answer option " . ['A', 'B', 'C', 'D'][$option] . " image removed successfully",
                'option' => $option
            ]);

        } catch (\Exception $e) {
            Log::error("Answer {$option} image deletion failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================


    protected function getFormData(): array
    {
        return [
            'skills' => Skill::orderBy('skill')->get(['id', 'skill']),
            'difficulties' => Difficulty::orderBy('id')->get(),
            'types' => Type::orderBy('id')->get(),
            'statuses' => Status::where('status', 'Public')->get()
        ];
    }

    public function getAvailableTracks()
    {
        try {
            $tracks = Track::whereHas('skills.questions')
            ->orderBy('track')
            ->get(['id', 'track', 'description']);

            return response()->json([
                'success' => true,
                'tracks' => $tracks
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading tracks', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading tracks: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getQuestionTypes()
    {
        try {
            $types = Type::orderBy('id')->get(['id', 'type', 'description']);

            return response()->json([
                'success' => true,
                'types' => $types
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading question types', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading question types: ' . $e->getMessage()
            ], 500);
        }
    }

    // =====================================================
    // SKILL-SPECIFIC QUESTION MANAGEMENT
    // =====================================================

    public function indexForSkill(Skill $skill)
    {
        $questions = $skill->questions()
        ->with(['difficulty', 'type', 'author'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        if (request()->expectsJson()) {
            return response()->json([
                'skill' => $skill,
                'questions' => $questions->items(),
                'pagination' => [
                    'current_page' => $questions->currentPage(),
                    'last_page' => $questions->lastPage(),
                    'total' => $questions->total()
                ]
            ]);
        }

        return view('admin.skills.questions.index', compact('skill', 'questions'));
    }

    public function createForSkill(Skill $skill)
    {
        $data = array_merge($this->getFormData(), ['skill' => $skill]);
        return view('admin.skills.questions.create', $data);
    }

    public function storeForSkill(Request $request, Skill $skill)
    {
        $request->merge(['skill_id' => $skill->id]);
        return $this->store($request);
    }

    public function showForSkill(Skill $skill, Question $question)
    {
        if ($question->skill_id !== $skill->id) {
            abort(404, 'Question not found in this skill');
        }

        return $this->show($question);
    }

    public function showGenerationForm(Skill $skill)
    {
        $data = [
            'skill' => $skill->load(['tracks.level']),
            'difficulties' => Difficulty::all(),
            'types' => Type::all(),
            'existing_count' => $skill->questions()->count()
        ];

        return view('admin.skills.questions.generate', $data);
    }

}