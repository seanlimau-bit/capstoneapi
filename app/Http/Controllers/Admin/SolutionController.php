<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolutionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // ensure Auth::user() is available
    }

    public function index(Request $request)
    {
        $solutions = Solution::query()
            ->when($request->filled('question_id'), fn($q) => $q->where('question_id', $request->integer('question_id')))
            ->latest('id')
            ->paginate(50);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $solutions]);
        }
        return view('admin.solutions.index', compact('solutions'));
    }

    public function create()
    {
        return redirect()->route('admin.solutions.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'solution'    => ['required', 'string'],
            'status_id'   => ['nullable', 'integer'],
        ]);

        $question = Question::findOrFail($data['question_id']);
        $userId = Auth::user()->id;

        $solution = new Solution();
        $solution->question_id = $question->id;
        $solution->solution    = $data['solution'];
        $solution->status_id   = $data['status_id'] ?? 1;
        $solution->user_id     = $userId;          // <— as requested
        $solution->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $solution->id, 'data' => $solution]);
        }
        return redirect()->back()->with('status', 'Solution created');
    }

    public function show(Request $request, Solution $solution)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $solution]);
        }
        return view('admin.solutions.show', compact('solution'));
    }

    public function edit(Solution $solution)
    {
        return redirect()->route('admin.solutions.index');
    }

    public function update(Request $request, Solution $solution)
    {
        $data = $request->validate([
            'solution'  => ['sometimes', 'string'],
            'status_id' => ['sometimes', 'integer'],
        ]);

        if (array_key_exists('solution',  $data)) $solution->solution  = $data['solution'];
        if (array_key_exists('status_id', $data)) $solution->status_id = $data['status_id'];
        // keep original author; do not overwrite user_id on edit
        $solution->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $solution]);
        }
        return redirect()->back()->with('status', 'Solution updated');
    }

    public function destroy(Request $request, Solution $solution)
    {
        $solution->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('status', 'Solution deleted');
    }
}
