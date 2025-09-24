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
use App\Models\Level;
use App\Models\User;
use App\Models\Status;
use App\Models\Difficulty;
use App\Models\Type;
use App\Services\QuestionGenerationService;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    protected $questionService;

    public function __construct(QuestionGenerationService $questionService)
    {
        $this->questionService = $questionService;
    }

    // =====================================================
    // GLOBAL QUESTION MANAGEMENT
    // =====================================================

    public function index(Request $request)
    {
        $query = Question::with(['skill.tracks.level', 'difficulty', 'type', 'author'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('skill_id'))     $query->where('skill_id', $request->skill_id);
        if ($request->filled('qa_status'))    $query->where('qa_status', $request->qa_status);
        if ($request->filled('author_id'))    $query->where('user_id', $request->author_id);
        if ($request->filled('source'))       $query->where('source', 'like', '%'.$request->source.'%');
        if ($request->filled('difficulty_id'))$query->where('difficulty_id', $request->difficulty_id);
        if ($request->filled('status_id'))    $query->where('status_id', $request->status_id);
        if ($request->filled('type_id'))      $query->where('type_id', $request->type_id);
        if ($request->filled('field_id')) {
            $query->whereHas('skill.field', fn ($fq) => $fq->where('id', $request->field_id));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('question', 'like', $term)
                  ->orWhere('explanation', 'like', $term)
                  ->orWhere('source', 'like', $term)
                  ->orWhereHas('skill', fn ($sq) => $sq->where('skill', 'like', $term))
                  ->orWhereHas('author', fn ($aq) => $aq->where('name', 'like', $term));
            });
        }

        $questions = $query->paginate(50)->withQueryString();

        // Totals based on the SAME filters
        $base = clone $query;
        $totals = [
            'total'    => (clone $base)->count(),
            'approved' => (clone $base)->where('qa_status', 'approved')->count(),
            'pending'  => (clone $base)->where('qa_status', 'unreviewed')->count(),
            'flagged'  => (clone $base)->where('qa_status', 'flagged')->count(),
        ];

        // Filter dropdown data (unchanged)
        $filterOptions = [
            'qa_statuses' => Question::getQaStatuses(),
            'skills'      => Skill::orderBy('skill')->get(['id','skill']),
            'authors'     => User::whereHas('questions')->orderBy('name')->get(['id','name']),
            'sources'     => Question::whereNotNull('source')->where('source','!=','')->distinct()->orderBy('source')->pluck('source'),
        ];

        // AJAX: return HTML tbody built with your reusable row component
        if ($request->ajax() || $request->wantsJson()) {
            $html = view('admin.questions.table-body', [
                'questions'    => $questions->items(),
                'skillId'      => $request->input('skill_id'),
                'withCheckbox' => true,
            ])->render();

            return response()->json([
                'html'        => $html,
                'num_pages'   => $questions->lastPage(),
                'current_page'=> $questions->currentPage(),
                'per_page'    => $questions->perPage(),
                'total'       => $questions->total(),
                'totals'      => $totals,
                'filter_options' => $filterOptions,
            ]);
        }

        // Initial render
        $skill = $request->filled('skill_id') ? Skill::find($request->skill_id) : null;

        return view('admin.questions.index', compact('questions','skill','totals','filterOptions'));
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
        'answer1' => 'required|string|max:255',
        'answer2' => 'required|string|max:255',
        'answer3' => 'required|string|max:255',
        'correct_answer' => 'required|integer|between:0,3',
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

    public function show(Question $question)
    {
        $question->load(['skill', 'difficulty', 'type', 'author', 'hints', 'solutions.author']);

        $data = [
            'question' => $question,
            'skills' => Skill::orderBy('skill')->get(['id', 'skill']),
            'difficulties' => Difficulty::orderBy('id')->get(['id', 'short_description']),
            'types' => Type::orderBy('id')->get(['id', 'type']),
            'qaStatuses' => [
                ['value' => 'unreviewed', 'label' => 'Unreviewed', 'class' => 'bg-secondary'],
                ['value' => 'ai_generated', 'label' => 'AI Generated', 'class' => 'bg-info'],
                ['value' => 'approved', 'label' => 'Approved', 'class' => 'bg-success'],
                ['value' => 'flagged', 'label' => 'Flagged', 'class' => 'bg-danger'],
                ['value' => 'needs_revision', 'label' => 'Needs Revision', 'class' => 'bg-warning']
            ]
        ];

        if (request()->expectsJson()) {
            return response()->json($data);
        }

        return view('admin.questions.show', $data);
    }

/**
 * Update a specific field of a question (for inline editing)
 */
public function updateField(Request $request, Question $question)
{
    try {
        $field = $request->input('field');
        $value = $request->input('value');
        
        // Define validation rules for each field
        $validationRules = [
            'skill_id' => 'nullable|exists:skills,id',
            'difficulty_id' => 'nullable|exists:difficulties,id',
            'type_id' => 'nullable|exists:types,id',
            'qa_status' => 'required|in:unreviewed,approved,flagged,needs_revision,ai_generated',
            'question' => 'required|string|max:1000',
            'answer0' => 'nullable|string|max:255',
            'answer1' => 'nullable|string|max:255',
            'answer2' => 'nullable|string|max:255',
            'answer3' => 'nullable|string|max:255',
            'correct_answer' => 'nullable|integer',
            'explanation' => 'nullable|string|max:2000',
            'hint' => 'nullable|string|max:500',
            'calculator' => 'nullable|string|max:255'
        ];
        
        // Check if field is allowed
        if (!array_key_exists($field, $validationRules)) {
            return response()->json([
                'success' => false,
                'message' => 'Field not allowed for inline editing'
            ], 400);
        }
        
        // Validate the value
        $validator = \Validator::make(
            [$field => $value],
            [$field => $validationRules[$field]]
        );
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first($field)
            ], 422);
        }
        
        // Update and save
        $question->$field = $value;
        $question->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully',
            'field' => $field,
            'value' => $value
        ]);
        
    } catch (\Exception $e) {        
        return response()->json([
            'success' => false,
            'message' => 'Update failed'
        ], 500);
    }
}

public static function getQaStatuses() {
    try {
            // Try to get from ENUM definition
        $type = DB::select("SHOW COLUMNS FROM questions LIKE 'qa_status'")[0]->Type;
        preg_match('/^enum\(\'(.+)\'\)$/', $type, $matches);
        return explode("','", $matches[1]);
    } catch (Exception $e) {
            // Fallback to distinct values from data
        return self::distinct()->pluck('qa_status')->filter()->sort()->values()->toArray();
    }
}
/**
 * Get validation rules for specific fields
 */
private function getFieldValidationRules(string $field): ?string
{
    return match($field) {
        'skill_id' => 'required|exists:skills,id',
        'difficulty_id' => 'required|exists:difficulties,id',
        'type_id' => 'required|exists:types,id',
        'qa_status' => 'required|in:unreviewed,ai_generated,approved,flagged,needs_revision',
        'question' => 'required|string|max:2000',
        'answer0', 'answer1', 'answer2', 'answer3' => 'required|string|max:255',
        'correct_answer' => 'required',
        'explanation', 'hint' => 'nullable|string|max:1000',
        'calculator' => 'nullable|in:scientific,basic',
        default => null
    };
}

/**
 * Get formatted display value for response
 */
private function getFormattedDisplayValue(Question $question, string $field, $value): array
{
    switch ($field) {
        case 'difficulty_id':
        return [
            'display_value' => $question->difficulty->short_description ?? 'Unknown',
            'badge_class' => $this->getDifficultyBadgeClass($question->difficulty->short_description ?? 'medium')
        ];

        case 'type_id':
        return [
            'display_value' => $question->type->type ?? 'Unknown'
        ];

        case 'skill_id':
        return [
            'display_value' => $question->skill->skill ?? 'Unknown'
        ];

        case 'qa_status':
        return [
            'display_value' => ucfirst(str_replace('_', ' ', $value)),
            'badge_class' => $this->getQaStatusBadgeClass($value)
        ];

        default:
        return ['display_value' => $value];
    }
}

/**
 * Get CSS badge class for difficulty
 */
private function getDifficultyBadgeClass(string $difficulty): string
{
    return match(strtolower($difficulty)) {
        'easy' => 'bg-success',
        'medium' => 'bg-warning', 
        'hard' => 'bg-danger',
        default => 'bg-secondary'
    };
}

/**
 * Get CSS badge class for QA status
 */
private function getQaStatusBadgeClass(string $status): string
{
    return match($status) {
        'approved' => 'bg-success',
        'flagged' => 'bg-danger',
        'needs_revision' => 'bg-warning',
        'ai_generated' => 'bg-info',
        'unreviewed' => 'bg-secondary',
        default => 'bg-secondary'
    };
}

public function update(Request $request, Question $question)
{
    $validated = $request->validate([
        'skill_id' => 'required|exists:skills,id',
        'difficulty_id' => 'required|exists:difficulties,id',
        'type_id' => 'required|exists:types,id',
        'question' => 'required|string|max:2000',
        'answer0' => 'required|string|max:255',
        'answer1' => 'required|string|max:255',
        'answer2' => 'required|string|max:255',
        'answer3' => 'required|string|max:255',
        'correct_answer' => 'required|integer|between:0,3',
        'explanation' => 'nullable|string|max:1000',
        'calculator' => 'nullable|in:scientific,basic',
    ]);

    DB::beginTransaction();
    try {
        $question->update($validated);

        DB::commit();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'question' => $question->fresh(['skill', 'difficulty', 'type'])
            ]);
        }

        return redirect()->route('admin.questions.show', $question)
        ->with('success', 'Question updated successfully');

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Question update failed', ['question_id' => $question->id, 'error' => $e->getMessage()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update question: ' . $e->getMessage()
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

public function generateQuestions(Request $request, \App\Services\QuestionGenerationService $service)
{

    $v = $request->validate([
        'question_id'    => ['nullable','integer','exists:questions,id'],
        'skill_id'       => ['nullable','integer','exists:skills,id'],
        'question_count' => ['required','integer','min:1','max:50'],
    ]);
    if (! $request->filled('question_id') && ! $request->filled('skill_id')) {
        return response()->json(['success' => false, 'message' => 'Provide question_id or skill_id.'], 422);
    }
    $created = $request->filled('question_id')
    ? $service->generateQuestionVariationsById(
        (int)$v['question_id'], 
        (int)$v['question_count'])
    : $service->generateQuestionsBySkillId(
        (int)$v['skill_id'], 
        (int)$v['question_count'],
        [
            'difficulty' => $v['difficulty_distribution'] ?? 'auto',
            'focus_areas' => $v['focus_areas'] ?? '',
            'question_types' => $v['question_types'] ?? 'mixed'
        ]
    );
    $imagesQueued = 0;
    foreach ($created as $question) {
        if (!empty($question->question_image) || 
            !empty($question->answer0_image) || 
            !empty($question->answer1_image) || 
            !empty($question->answer2_image) || 
            !empty($question->answer3_image)) {
            $imagesQueued++;
        }
    }
    
    $message = 'Questions generated successfully';
    if ($imagesQueued > 0) {
        $estimatedMinutes = ceil($imagesQueued * 0.5); // Estimate 30 seconds per image
        $message = "Questions generated successfully. {$imagesQueued} images are being generated in the background (approximately {$estimatedMinutes} minutes).";
    }
    
    return response()->json([
        'success' => true,
        'message' => $message,
        'questions_created' => count($created),
        'question_ids' => collect($created)->pluck('id')->all(),
        'images_queued' => $imagesQueued,
        'estimated_time_minutes' => $imagesQueued > 0 ? ceil($imagesQueued * 0.5) : 0
    ]);
}



    // =====================================================
    // IMAGE MANAGEMENT METHODS
    // =====================================================

public function uploadQuestionImage(Request $request, Question $question)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:6144'
    ]);

    try {
        $image = $request->file('image');
        $filename = time() . '_' . $question->id . '_question.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('uploads/questions', $filename, 'public');

            // Remove old image if exists
        if ($question->question_image) {
            $oldPath = str_replace('/storage/', '', $question->question_image);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $question->question_image = '/storage/' . $path;
        $question->save();

        return response()->json([
            'success' => true,
            'message' => 'Question image uploaded successfully',
            'image_url' => asset('storage/' . $path)
        ]);

    } catch (\Exception $e) {
        Log::error('Question image upload failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
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

    /**
     * Format question response with consistent data types
     */
    private function formatQuestionResponse($question, $originalQuestion = null): array
    {
    // Default to original question's difficulty for variations
    $difficulty = 'Medium'; // Fallback default
    
    if ($originalQuestion && is_object($originalQuestion->difficulty)) {
        $difficulty = $originalQuestion->difficulty->short_description ?? 'Medium';
    }
    
    // Only override if this specific question has a different difficulty set
    if (is_object($question->difficulty)) {
        $difficulty = $question->difficulty->short_description ?? $difficulty;
    } elseif (is_string($question->difficulty)) {
        $difficulty = $question->difficulty;
    }
    
    // Same logic for type
    $type = 'Multiple Choice'; // Fallback default
    
    if ($originalQuestion && is_object($originalQuestion->type)) {
        $type = $originalQuestion->type->type ?? 'Multiple Choice';
    }
    
    if (is_object($question->type)) {
        $type = $question->type->type ?? $type;
    } elseif (is_string($question->type)) {
        $type = $question->type;
    }

    return [
        'id' => $question->id ?? null,
        'question' => $question->question,
        'answer0' => $question->answer0,
        'answer1' => $question->answer1,
        'answer2' => $question->answer2,
        'answer3' => $question->answer3,
        'correct_answer' => $question->correct_answer,
        'explanation' => $question->explanation ?? '',
        'difficulty' => $difficulty,
        'type' => $type
    ];
}
protected function buildErrorResponse(string $title, string $message, int $requested): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'success' => false,
        'message' => $title,
        'error_details' => $message,
        'questions_created' => 0,
        'questions' => []
    ], 400);
}

protected function buildZeroQuestionsResponse(int $requested, int $existingCount): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => 'Successfully generated 0 questions',
        'questions_created' => 0,
        'questions' => []
    ]);
}

protected function importFromQuestionBank(array $data, Skill $skill): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => 'Question bank import feature coming soon',
        'questions_created' => 0,
        'questions' => []
    ]);
}

protected function copyFromSimilarSkills(array $data, Skill $skill): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => 'Copy from similar skills feature coming soon',
        'questions_created' => 0,
        'questions' => []
    ]);
}

protected function createManualQuestions(array $data, Skill $skill): \Illuminate\Http\JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => 'Manual question template created',
        'questions_created' => 0,
        'template' => [
            'skill_id' => $skill->id,
            'questions' => array_fill(0, $data['number_of_questions'], [
                'question' => '',
                'answer0' => '',
                'answer1' => '',
                'answer2' => '',
                'answer3' => '',
                'correct_answer' => 0,
                'explanation' => '',
                'difficulty_id' => 2,
                'type_id' => 1
            ])
        ]
    ]);
}

protected function getFormData(): array
{
    return [
        'skills' => Skill::orderBy('skill')->get(['id', 'skill']),
        'difficulties' => Difficulty::orderBy('id')->get(),
        'types' => Type::orderBy('id')->get(),
        'statuses' => Status::where('status', 'Public')->get()
    ];
}

protected function getDropdownOptions(): array
{
    return [
        'difficulties' => Difficulty::select('id as value', 'short_description as text')->get(),
        'qa_statuses' => [
            ['value' => 'unreviewed', 'text' => 'Unreviewed', 'class' => 'bg-warning'],
            ['value' => 'ai_generated', 'text' => 'AI Generated', 'class' => 'bg-info'],
            ['value' => 'approved', 'text' => 'Approved', 'class' => 'bg-success'],
            ['value' => 'flagged', 'text' => 'Flagged', 'class' => 'bg-danger'],
            ['value' => 'needs_revision', 'text' => 'Needs Revision', 'class' => 'bg-warning']
        ],
        'question_types' => Type::select('id as value', 'type as text')->get(),
        'skills' => Skill::select('id as value', 'skill as text')->orderBy('skill')->get()
    ];
}

protected function getQuestionTotals(): array
{
    return [
        'total' => Question::count(),
        'approved' => Question::where('qa_status', 'approved')->count(),
        'pending' => Question::where('qa_status', 'unreviewed')->count(),
        'flagged' => Question::where('qa_status', 'flagged')->count(),
        'ai_generated' => Question::where('qa_status', 'ai_generated')->count()
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