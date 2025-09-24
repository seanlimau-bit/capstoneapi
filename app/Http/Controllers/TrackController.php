<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\Skill;
use App\Models\Level;
use App\Models\Status;
use App\Models\Field;
use App\Http\Requests\CreateTrackRequest;
use App\Models\Course;
use App\Http\Requests\UpdateRequest;
use Illuminate\Support\Facades\Auth;
use DB;

class TrackController extends Controller
{
    public function __construct(){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tracks = Track::with(['skills', 'level', 'status'])
        ->withCount('skills')
        ->orderBy('track')
        ->get();
        
        // Add reference data for consistency
        $statuses = Status::select('id', 'status')->get();
        $levels = Level::select('id', 'level', 'description')->get();
        
        return view('admin.tracks.index', compact('tracks', 'statuses', 'levels'));
    }

    public function create(){
        $user = Auth::user();
        $public_tracks = $user->is_admin ? Track::select('id','track') : Track::whereStatusId(3)->select('id','track')->get();
        $my_tracks = $user->tracks()->select('id','track')->get();

        return response()->json([
            'message' => 'Fields for create track fetched.',
            'levels' => \App\Level::select('id','level','description')->get(), 
            'statuses' => \App\Status::select('id','status','description')->get(),
            'fields' => \App\Field::select('id','field','description')->get(), 
            'my_tracks' => $my_tracks, 
            'public_tracks' => $public_tracks,
            'skills' => \App\Skill::select('id','skill','description')->get(),
            'code' => 200
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTrackRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new courses', 'code'=>403],403);
        }
        $values = $request->except('skill_ids');
        $values['user_id'] = $user->id;
        $track = Track::create($values);
        if ($request->skills_ids){
            foreach ($request->skill_ids as $skill_id) {
               $skill = \App\Skill::find($skill_id);
               $skill->tracks()->sync($track->id,['skill_order'=>$track->maxSkill($track)? $track->maxSkill($track)->skill_order + 1:1], FALSE);
           }
       }
       return response()->json(['message' => 'Track correctly added.', 'track'=>$track,'code'=>201]);
   }

    /**
     * Display the specified resource.
     */
    public function show(Track $track)
    {
        // Load track with all relationships for the show page
        $track->load(['skills.questions', 'level', 'status', 'skills.status']);
        
        // Get reference data for inline editing
        $statuses = Status::select('id', 'status')->get();
        $levels = Level::select('id', 'level', 'description')->get();
        $fields = Field::select('id', 'field', 'description')->get();
        
        return view('admin.tracks.show', compact('track', 'statuses', 'levels', 'fields'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Track $track)
    {
        // Check if this is an inline edit request
        if ($request->has('field') && $request->has('value')) {
            $field = $request->input('field');
            $value = $request->input('value');
            
            // Validate the field
            $allowedFields = ['track', 'description', 'status_id', 'level_id', 'field_id'];
            if (!in_array($field, $allowedFields)) {
                return response()->json(['success' => false, 'message' => 'Invalid field'], 400);
            }
            
            // Handle empty values for nullable fields
            if (in_array($field, ['level_id', 'field_id']) && $value === '') {
                $value = null;
            }
            
            try {
                $track->update([$field => $value]);
                return response()->json(['success' => true, 'message' => 'Updated successfully']);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 400);
            }
        }
        
        // Handle regular form updates here if needed
        // ... your existing update logic
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Track $track)
    {
        try {
        // Check if track has skills attached
            if ($track->skills()->count() > 0) {
                if (!$request->has('remove_dependencies')) {
                // First time - ask for confirmation
                    return response()->json([
                        'success' => false,
                        'message' => 'This track has ' . $track->skills()->count() . ' skills attached. Delete anyway?',
                        'requires_confirmation' => true,
                        'code' => 409
                    ], 409);
                }

            // User confirmed - detach skills first
                $track->skills()->detach();
            }

        // Now delete the track
            $track->delete();

            return response()->json([
                'success' => true,
                'message' => 'Track deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting track: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get skills for track management modal
     */
    public function skills(Track $track)
    {
        $assignedSkills = $track->skills()->with('status')->withCount('questions')->get();
        $availableSkills = Skill::whereNotIn('id', $assignedSkills->pluck('id'))
        ->with('status')
        ->withCount('questions')
        ->orderBy('skill')
        ->get();
        
        return response()->json([
            'success' => true,
            'track' => $track,
            'assignedSkills' => $assignedSkills,
            'availableSkills' => $availableSkills
        ]);
    }

    /**
     * Add skill to track
     */
    public function addSkill(Track $track, Skill $skill)
    {
        if (!$track->skills->contains($skill->id)) {
            $track->skills()->attach($skill->id);
            return response()->json(['success' => true, 'message' => 'Skill added to track']);
        }
        
        return response()->json(['success' => false, 'message' => 'Skill already assigned to this track']);
    }

    /**
     * Remove skill from track
     */
    public function removeSkill(Track $track, Skill $skill)
    {
        $track->skills()->detach($skill->id);
        return response()->json(['success' => true, 'message' => 'Skill removed from track']);
    }

    /**
     * Copy/duplicate track
     */
    public function duplicate(Request $request, \App\Models\Track $track)
    {
        $user = Auth::user();

    // Return JSON 403 for AJAX, redirect for full requests
        $deny = function($msg = 'Only administrators can duplicate tracks') use ($request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        };

        if (!$user || !$user->canAccessAdmin()) {
            return $deny();
        }

        DB::beginTransaction();
        try {
        // Clone the track
            $new = $track->replicate();
            $new->track   = $track->track.' (Copy)';
            $new->user_id = $user->id;

        // If you have unique columns (e.g., slug/code), ensure uniqueness here.
        // Example:
        // if (!empty($track->slug)) {
        //     $new->slug = \Str::slug($new->track.'-'.\Str::random(6));
        // }

            $new->save();

        // Copy skills if requested (works for JSON or form posts)
            if ($request->boolean('copy_skills')) {
                $skillIds = $track->skills()->pluck('skills.id')->all();
                if (!empty($skillIds)) {
                    $new->skills()->attach($skillIds);
                }
            }

            DB::commit();

        // AJAX: return JSON the frontend can act on
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'   => true,
                    'message'   => 'Track duplicated successfully',
                    'track_id'  => $new->id,
                    'redirect'  => route('admin.tracks.show', $new->id),
                    'track'     => ['id' => $new->id], 
                ], 200);
            }

        // Non-AJAX: do normal redirect
            return redirect()
            ->route('admin.tracks.show', $new->id)
            ->with('success', 'Track duplicated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error duplicating track: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error duplicating track: '.$e->getMessage());
        }
    }


    /**
     * Export skills for this track
     */
    public function exportSkills(Track $track)
    {
        // Implementation depends on your export requirements
        // For now, just redirect back
        return redirect()->back()->with('info', 'Export functionality coming soon');
    }
}