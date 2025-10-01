<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFieldRequest;
use App\Models\Field;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\LookupOptionsService;

class FieldController extends Controller
{
    /**
     * API + Web index entrypoint.
     */
public function index(Request $request)
{
    // If it's NOT an AJAX request expecting JSON, go to webIndex for Blade view
    if (!$request->ajax()) {
        return $this->webIndex($request);
    }

    // Continue with JSON logic for AJAX requests
    $query = Field::with(['tracks', 'status'])
        ->orderBy('created_at', 'desc');

    // Filter by status_id
    if ($request->filled('status_id')) {
        $query->where('status_id', $request->status_id);
    }

    // Add sorting support
    if ($request->filled('sort')) {
        $sortField = $request->sort;
        $direction = $request->filled('direction') ? $request->direction : 'asc';
        $query->orderBy($sortField, $direction);
    }

    if ($request->filled('has_tracks')) {
        $request->has_tracks === '1'
            ? $query->has('tracks')
            : $query->doesntHave('tracks');
    }

    if ($request->filled('search')) {
        $searchTerm = $request->search;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('field', 'LIKE', "%{$searchTerm}%")
              ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        });
    }

    $fields = $query->get();

    // Calculate counts for each field
    $fields->each(function (Field $field) {
        $field->tracks_count = $field->tracks()->count();
        $field->questions_count = 0;
        $field->skills_count = 0;
    });

    // Get status counts dynamically by status name
    $publicStatus = \DB::table('statuses')->where('status', 'Public')->first();
    $draftStatus = \DB::table('statuses')->where('status', 'Draft')->first();
    $privateStatus = \DB::table('statuses')->whereIn('status', ['Private', 'Restricted'])->first();

    return response()->json([
        'fields' => $fields,
        'totals' => [
            'total' => Field::count(),
            'public' => $publicStatus ? Field::where('status_id', $publicStatus->id)->count() : 0,
            'draft' => $draftStatus ? Field::where('status_id', $draftStatus->id)->count() : 0,
            'private' => $privateStatus ? Field::where('status_id', $privateStatus->id)->count() : 0,
        ],
        'num_pages' => 1,
    ]);
}
    /**
     * Web admin index with filters and search.
     */
    private function webIndex(Request $request)
    {
        $query = Field::with(['tracks', 'status'])->orderBy('created_at', 'desc');

        // Filter by status name on the related status
        if ($request->filled('status_id')) {
            $query->whereHas('status', function ($q) use ($request) {
                $q->where('status', $request->status_id);
            });
        }

        // Filter fields by presence of tracks
        if ($request->filled('has_tracks')) {
            $request->has_tracks === '1'
            ? $query->has('tracks')
            : $query->doesntHave('tracks');
        }

        // Search on field name or description
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('field', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        $fields = $query->get();

        // Compute counts using direct database queries to avoid relationship issues
        $fields->each(function (Field $field) {
            $field->tracks_count = $field->tracks()->count();
            
            // Use Eloquent relationships instead of raw DB queries
            $trackIds = $field->tracks()->pluck('id');
            
            $field->questions_count = Question::whereHas('skill', function($query) use ($trackIds) {
                $query->whereHas('tracks', function($subQuery) use ($trackIds) {
                    $subQuery->whereIn('tracks.id', $trackIds);
                });
            })->count();
            
            $field->skills_count = Skill::whereHas('tracks', function($query) use ($trackIds) {
                $query->whereIn('tracks.id', $trackIds);
            })->count();
        });

        // AJAX partial table for filters
        if ($request->ajax()) {
            return view('admin.fields.partials.fields-table', compact('fields'));
        }

        // Full page
        return view('admin.fields.index', compact('fields'));
    }

    /**
     * Store a newly created Field.
     */
    public function store(CreateFieldRequest $request)
    {
        $user = Auth::user();

        if (!$user->is_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only administrators can create a new field', 'code' => 403], 403);
            }
            abort(403, 'Only administrators can create a new field');
        }

        $values = $request->validated();
        $values['user_id'] = $user->id;

        $field = Field::create($values);

        // Web responses
        if (!$request->expectsJson()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Field created successfully',
                    'field'   => $field,
                ]);
            }
            return redirect()
            ->route('admin.fields.index')
            ->with('success', 'Field created successfully');
        }

        // API response
        return response()->json([
            'message' => 'Field is now added',
            'code'    => 201,
            'field'   => $field,
        ], 201);
    }

    /**
     * Show a Field.
     */
    public function show(Field $field, Request $request, LookupOptionsService $lookups)
    {
        // Handle API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Successful retrieval of field.',
                'field' => $field,
                'code' => 200,
            ], 200);
        }
        
        // Load relationships
        $field->load(['tracks.status', 'tracks.level','tracks.skills', 'user', 'status']);
        
        // Calculate counts
        $field->tracks_count = $field->tracks()->count();
        
        if ($field->tracks_count > 0) {
            $trackIds = $field->tracks()->pluck('id')->toArray();
            
            $field->questions_count = DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->whereIn('skill_track.track_id', $trackIds)
            ->count();

            $field->active_questions_count = DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('statuses', 'questions.status_id', '=', 'statuses.id')
            ->whereIn('skill_track.track_id', $trackIds)
            ->where('statuses.status', 'active')
            ->count();

            $field->skills_count = DB::table('skills')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->whereIn('skill_track.track_id', $trackIds)
            ->distinct('skills.id')
            ->count();
        } else {
            $field->questions_count = 0;
            $field->active_questions_count = 0;
            $field->skills_count = 0;
        }
        
        // Get lookup options
        $statuses = $lookups->statuses();
        $levels = $lookups->levels();
        
        return view('admin.fields.show', compact('field', 'statuses', 'levels'));
    }
    /**
     * Update a Field.
     */
    public function update(Request $request, Field $field)
    {
    // Add this at the very top
        \Log::info('Update request received', [
            'ajax' => $request->ajax(),
            'has_field' => $request->has('field'),
            'all_data' => $request->all(),
            'field_name' => $request->input('field'),
            'value' => $request->input('value')
        ]);

    // Inline AJAX update from web UI
        if ($request->ajax() && $request->has('field')) {
            $fieldName = $request->input('field');
            $value     = $request->input('value');

            \Log::info('Inline update detected', [
                'field_name' => $fieldName,
                'value' => $value,
                'field_id' => $field->id
            ]);

            $rules = [
                'field'       => 'required|string|max:255',
                'description' => 'nullable|string',
                'image'       => 'nullable|string|max:255',
                'status_id'   => 'nullable|exists:statuses,id',
            ];

            if (!array_key_exists($fieldName, $rules)) {
                \Log::warning('Invalid field name', ['field_name' => $fieldName]);
                return response()->json(['success' => false, 'message' => 'Invalid field name'], 400);
            }

            $ruleForValue = preg_replace('/\brequired\|?/', 'nullable|', $rules[$fieldName]);
            $request->validate(['value' => $ruleForValue]);

            $field->update([$fieldName => $value]);

            \Log::info('Field updated', ['field' => $field->fresh()->toArray()]);

            return response()->json(['success' => true, 'message' => 'Field updated successfully']);
        }

    // ... rest of the method
    }
    /**
     * Delete a Field.
     */
    public function destroy($id)
    {
        try {
            $field = Field::findOrFail($id);
            $field->delete();

            // Return JSON response for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Field deleted successfully'
                ]);
            }

            // Redirect for non-AJAX requests
            return redirect()->route('admin.fields.index')
            ->with('success', 'Field deleted successfully');
            
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting field: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
            ->with('error', 'Error deleting field');
        }
    }

    /**
     * Questions list for a Field, for modal display.
     */
    public function questions(Field $field, Request $request)
    {
        // Use direct DB query instead of nested relationships
        $query = DB::table('questions')
        ->join('skills', 'questions.skill_id', '=', 'skills.id')
        ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
        ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
        ->where('tracks.field_id', $field->id)
        ->select('questions.*', 'skills.skill as skill_name', 'tracks.track as track_name');

        if ($request->filled('difficulty')) {
            $query->where('questions.difficulty', $request->difficulty);
        }
        if ($request->filled('type')) {
            $query->where('questions.type', $request->type);
        }
        if ($request->filled('status')) {
            $query->join('statuses', 'questions.status_id', '=', 'statuses.id')
            ->where('statuses.status', $request->status);
        }

        $questions = $query->paginate(20);

        return view('admin.fields.partials.questions-modal', compact('field', 'questions'));
    }

    /**
     * Manage Track assignments for a Field.
     * Field ⇄ Track is one-to-many via tracks.field_id.
     */
    public function manageTracks(Request $request, Field $field)
    {
        if ($request->isMethod('get')) {
            $currentTracks   = $field->tracks()->pluck('id')->toArray();
            $availableTracks = Track::whereNull('field_id')
            ->orWhere('field_id', $field->id)
            ->get();

            return response()->json([
                'current_tracks'   => $currentTracks,
                'available_tracks' => $availableTracks,
            ]);
        }

        // Update assignments
        $validated = $request->validate([
            'track_ids'   => 'array',
            'track_ids.*' => 'exists:tracks,id',
        ]);

        $trackIds = $validated['track_ids'] ?? [];

        // Detach from this field any tracks not in the submitted list
        Track::where('field_id', $field->id)
        ->whereNotIn('id', $trackIds)
        ->update(['field_id' => null]);

        // Attach to this field any tracks that are selected and currently unassigned
        if (!empty($trackIds)) {
            Track::whereIn('id', $trackIds)
            ->where(function ($q) use ($field) {
                $q->whereNull('field_id')->orWhere('field_id', $field->id);
            })
            ->update(['field_id' => $field->id]);
        }

        return response()->json(['success' => true, 'message' => 'Tracks updated successfully']);
    }

    /**
     * Add one Track to this Field.
     */
    public function addTrack(Request $request, Field $field)
    {
        $validated = $request->validate([
            'track_id' => 'required|exists:tracks,id',
        ]);

        $track = Track::find($validated['track_id']);

        if ($track->field_id == $field->id) {
            return response()->json(['success' => false, 'message' => 'Track is already assigned to this field'], 400);
        }

        if (!is_null($track->field_id) && $track->field_id !== $field->id) {
            return response()->json(['success' => false, 'message' => 'Track is already assigned to another field'], 400);
        }

        $track->update(['field_id' => $field->id]);

        return response()->json([
            'success' => true,
            'message' => 'Track added successfully',
            'track'   => $track,
        ]);
    }

    /**
     * Remove one Track from this Field.
     */
    public function removeTrack(Request $request, Field $field, Track $track)
    {
        if ($track->field_id != $field->id) {
            return response()->json(['success' => false, 'message' => 'Track does not belong to this field'], 400);
        }

        $track->update(['field_id' => null]);

        return response()->json(['success' => true, 'message' => 'Track removed successfully']);
    }

    /**
     * Export Field data (JSON attachment).
     */
    public function export(Field $field)
    {
        $data = [
            'field'     => $field->toArray(),
            'tracks'    => $field->tracks()->get()->toArray(),
            // Questions through Skill ⇄ Track pivot, limited to tracks in this field
            'questions' => DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
            ->where('tracks.field_id', $field->id)
            ->select('questions.*')
            ->get()
            ->toArray(),
        ];

        $filename = 'field_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $field->field) . '_' . now()->format('Y-m-d') . '.json';

        return response()
        ->json($data)
        ->header('Content-Type', 'application/json')
        ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Duplicate a Field, without stealing tracks from the source Field.
     */
    public function duplicate(Field $field)
    {
        $newField        = $field->replicate();
        $newField->field = $field->field . ' (Copy)';
        $newField->user_id = Auth::id();
        $newField->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Field duplicated successfully (tracks not copied to avoid conflicts)',
            'field_id' => $newField->id,
        ]);
    }

    /**
     * Analytics for a Field.
     */
    public function analytics(Field $field)
    {
        // Use direct DB queries instead of nested relationships
        $analytics = [
            'questions_by_difficulty' => DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
            ->where('tracks.field_id', $field->id)
            ->select('questions.difficulty', DB::raw('count(*) as count'))
            ->groupBy('questions.difficulty')
            ->pluck('count', 'difficulty'),

            'questions_by_type' => DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
            ->where('tracks.field_id', $field->id)
            ->select('questions.type', DB::raw('count(*) as count'))
            ->groupBy('questions.type')
            ->pluck('count', 'type'),

            'questions_by_month' => DB::table('questions')
            ->join('skills', 'questions.skill_id', '=', 'skills.id')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
            ->where('tracks.field_id', $field->id)
            ->select(DB::raw('DATE_FORMAT(questions.created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month'),

            'tracks_count' => $field->tracks()->count(),

            // Distinct skills attached to any track of this field using direct DB query
            'skills_count' => DB::table('skills')
            ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
            ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
            ->where('tracks.field_id', $field->id)
            ->distinct('skills.id')
            ->count(),
        ];

        return response()->json($analytics);
    }
}