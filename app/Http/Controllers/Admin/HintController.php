<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hint;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // ensure Auth::user() is available
    }

    public function index(Request $request)
    {
        $hints = Hint::query()
            ->when($request->filled('question_id'), fn($q) => $q->where('question_id', $request->integer('question_id')))
            ->latest('id')
            ->paginate(50);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $hints]);
        }
        return view('admin.hints.index', compact('hints'));
    }

    public function create()
    {
        return redirect()->route('admin.hints.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'hint_level'  => ['required', 'integer', 'min:1', 'max:10'],
            'hint_text'   => ['required', 'string'],
        ]);

        $question = Question::findOrFail($data['question_id']);
        $userId = Auth::user()->id;

        $hint = new Hint();
        $hint->question_id = $question->id;
        $hint->hint_level  = $data['hint_level'];
        $hint->hint_text   = $data['hint_text'];
        $hint->user_id     = $userId;              // <— as requested
        $hint->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $hint->id, 'data' => $hint]);
        }
        return redirect()->back()->with('status', 'Hint created');
    }

    public function show(Request $request, Hint $hint)
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $hint]);
        }
        return view('admin.hints.show', compact('hint'));
    }

    public function edit(Hint $hint)
    {
        return redirect()->route('admin.hints.index');
    }

    public function update(Request $request, Hint $hint)
    {
        $data = $request->validate([
            'hint_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'hint_text'  => ['sometimes', 'string'],
        ]);

        if (array_key_exists('hint_level', $data)) $hint->hint_level = $data['hint_level'];
        if (array_key_exists('hint_text',  $data)) $hint->hint_text  = $data['hint_text'];
        // keep original author; do not overwrite user_id on edit
        $hint->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $hint]);
        }
        return redirect()->back()->with('status', 'Hint updated');
    }

    public function destroy(Request $request, Hint $hint)
    {
        $hint->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('status', 'Hint deleted');
    }
}
