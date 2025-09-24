<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Model imports
use App\Models\Skill;
use App\Models\Track;
use App\Models\SkillLink;
use App\Models\Question;
use App\Models\Status;
use App\Models\Level;
use App\Models\Type;

use App\Http\Requests\UpdateRequest;
use App\Http\Requests\CreateSkillRequest;

class SkillController extends Controller
{
    /**
     * Display a listing of skills for admin interface
     */
    public function index(Request $request)
    {
    // If it's NOT an AJAX request expecting JSON, go to webIndex for Blade view
        if (!($request->ajax() && $request->expectsJson())) {
            return $this->webIndex($request);
        }

    // Continue with JSON logic for AJAX requests
        $query = Skill::with(['tracks.level', 'status', 'questions'])
        ->withCount('questions')
        ->orderBy('created_at', 'desc');

    // Add filtering
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->filled('track_id')) {
            $query->whereHas('tracks', function($q) use ($request) {
                $q->where('tracks.id', $request->track_id);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('skill', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

    // Add sorting support
        if ($request->filled('sort')) {
            $sortField = $request->sort;
            $direction = $request->filled('direction') ? $request->direction : 'asc';
            $query->orderBy($sortField, $direction);
        }

        $skills = $query->get();

    // Calculate tracks count for each skill
        $skills->each(function ($skill) {
            $skill->tracks_count = $skill->tracks()->count();
        });

    // Return JSON for AJAX requests (match Fields structure)
        return response()->json([
            'skills' => $skills,
            'totals' => [
                'total' => $skills->count(),
                'public' => $skills->where('status.status', 'Public')->count(),
                'draft' => $skills->where('status.status', 'Draft')->count(),
                'private' => $skills->where('status.status', 'Only Me')->count(),
            ],
            'num_pages' => 1,
        ]);
    }

// Add webIndex method for Blade view (same as Fields pattern)
    private function webIndex(Request $request)
    {
        $skills = Skill::with([
            'links',
            'tracks.level',
            'user',
            'status',
            'questions' => function($query) {
                $query->select('id', 'skill_id', 'qa_status', 'created_at');
            }
        ])
        ->withCount('questions')
        ->orderBy('created_at', 'desc')
        ->get();

    // Calculate tracks count
        $skills->each(function ($skill) {
            $skill->tracks_count = $skill->tracks()->count();
        });

        return view('admin.skills.index', compact('skills'));
    }

    /**
     * Show the form for creating a new skill
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!request()->expectsJson()) {
            $statuses = Status::all();
            $tracks = Track::with('level')->get();
            
            return view('admin.skills.create', compact('statuses', 'tracks'));
        }

        if (!$user->is_admin) {
            return response()->json(['message'=>'Only administrators can create a new skill.', 'code'=>403], 403);
        }

        return response()->json([
            'message' => 'Skill create form data.', 
            'statuses' => Status::all(), 
            'my_tracks' => $user->tracks, 
            'public_tracks' => Track::all(), 
            'code' => 200
        ]);
    }

    /**
     * Store a newly created skill
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $message = 'Only administrators can create new skills';
            
            if (request()->expectsJson()) {
                return response()->json(['message' => $message, 'code' => 403], 403);
            }
            
            return redirect()->back()->with('error', $message);
        }

        $request->validate([
            'skill' => 'required|string|max:255',
            'description' => 'required|string',
            'status_id' => 'required|exists:statuses,id',
            'track_ids' => 'array',
            'track_ids.*' => 'exists:tracks,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $values = $request->except(['links', 'track_ids']);
            $values['user_id'] = $user->id;
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/skills'), $imageName);
                $values['image'] = 'images/skills/' . $imageName;
            }
            
            $skill = Skill::create($values);

            // Handle video links
            if ($request->hasFile('links')) {
                foreach ($request->file('links') as $key => $link) {
                    $timestamp = time() . '_' . $key;
                    SkillLink::create([
                        'skill_id' => $skill->id, 
                        'user_id' => $user->id, 
                        'status_id' => 4, 
                        'link' => 'videos/skills/' . $timestamp . '.mp4'
                    ]);

                    $link->move(public_path('videos/skills'), $timestamp . '.mp4');
                }
            }

            // Sync tracks
            if ($request->track_ids) {
                $trackIds = is_string($request->track_ids) ? json_decode($request->track_ids, true) : $request->track_ids;
                $skill->tracks()->sync($trackIds);
            }

            DB::commit();
            $skill->load(['links', 'tracks.level', 'user']);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Skill created successfully.', 
                    'skill' => $skill,
                    'code' => 201
                ], 201);
            }

            return redirect()->route('admin.skills.index')->with('success', 'Skill created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Error creating skill: ' . $e->getMessage(),
                    'code' => 500
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error creating skill: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified skill
     */
    public function show(Skill $skill)
    {
        $skill->load(['links', 'tracks.level', 'user', 'questions.difficulty', 'questions.type']);
        
        $statuses = Status::select('id', 'status', 'description')->get();
        $difficulties = \App\Models\Difficulty::select('id', 'short_description', 'description')->get();
        $questionTypes = Type::select('id', 'type', 'description')->get();
        $skills = Skill::select('id', 'skill')->orderBy('skill')->get();
        
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Skill fetched successfully.', 
                'skill' => $skill,
                'statuses' => $statuses,
                'difficulties' => $difficulties,
                'question_types' => $questionTypes,
                'skills' => $skills,
                'code' => 200
            ]);
        }
        
        return view('admin.skills.show', compact('skill', 'statuses', 'difficulties', 'questionTypes', 'skills'));
    }

    /**
     * Show the form for editing the skill
     */
    public function edit(Skill $skill)
    {
        $user = Auth::user();
        
        if ($user->id != $skill->user_id && !$user->is_admin) {
            $message = 'You have no access rights to edit this skill';
            
            if (request()->expectsJson()) {
                return response()->json(['message' => $message, 'code' => 403], 403);
            }
            
            return redirect()->back()->with('error', $message);
        }

        $skill->load(['links', 'tracks.level', 'user']);
        $statuses = Status::all();
        $tracks = Track::with('level')->get();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Skill edit form data.',
                'skill' => $skill,
                'statuses' => $statuses,
                'tracks' => $tracks,
                'code' => 200
            ]);
        }

        return view('admin.skills.edit', compact('skill', 'statuses', 'tracks'));
    }

    /**
     * Update the skill
     */
    public function update(Request $request, Skill $skill)
    {
        if ($request->has('field') && $request->has('value') && $request->expectsJson()) {
            return $this->handleInlineUpdate($request, $skill);
        }

        $request->validate([
            'skill' => 'required|string|max:255',
            'description' => 'required|string',
            'status_id' => 'required|exists:statuses,id',
            'track_ids' => 'array',
            'track_ids.*' => 'exists:tracks,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Handle new video links
            if ($request->hasFile('links')) {
                foreach ($request->file('links') as $key => $link) {
                    $timestamp = time() . '_' . $key;
                    SkillLink::create([
                        'skill_id' => $skill->id, 
                        'user_id' => $user->id, 
                        'status_id' => 4, 
                        'link' => 'videos/skills/' . $timestamp . '.mp4'
                    ]);

                    $link->move(public_path('videos/skills'), $timestamp . '.mp4');
                }
            }

            // Handle removing links
            if ($request->remove_links) {
                foreach ($request->remove_links as $link_id) {
                    if ($link_id != -1) {
                        SkillLink::findOrFail($link_id)->delete();
                    } else {
                        $skill->lesson_link = null;
                    }
                }
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                if ($skill->image && file_exists(public_path($skill->image))) {
                    unlink(public_path($skill->image));
                }
                
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/skills'), $imageName);
                $skill->image = 'images/skills/' . $imageName;
            }

            // Update tracks
            if ($request->has('track_ids')) {
                $trackIds = is_string($request->track_ids) ? json_decode($request->track_ids, true) : $request->track_ids;
                $skill->tracks()->sync($trackIds ?: []);
            }

            $skill->fill($request->except(['lesson_link', 'track_ids', 'links', 'remove_links', 'image']))->save();
            DB::commit();

            $skill->load(['links', 'tracks.level', 'user']);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Skill updated successfully',
                    'skill' => $skill, 
                    'code' => 200
                ]);
            }

            return redirect()->route('admin.skills.index')->with('success', 'Skill updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Error updating skill: ' . $e->getMessage(),
                    'code' => 500
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error updating skill: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Handle inline field updates
     */
    private function handleInlineUpdate(Request $request, Skill $skill)
    {
        $field = $request->input('field');
        $value = $request->input('value');
        
        $allowedFields = ['skill', 'description', 'status_id', 'check'];
        
        if (!in_array($field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Invalid field'], 400);
        }
        
        try {
            $skill->update([$field => $value]);
            return response()->json(['success' => true, 'message' => 'Updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete the skill
     */
    public function destroy(Request $request, Skill $skill)
    {
        $user = Auth::user();

        if ($user->id != $skill->user_id && !$user->is_admin) {
            $message = 'You have no access rights to delete this skill';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message, 
                    'code' => 403
                ], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        DB::beginTransaction();

        try {
            if ($skill->questions()->count() > 0) {
                $message = 'There are questions in this skill. Delete all questions first.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message, 
                        'code' => 409
                    ], 409);
                }

                return redirect()->back()->with('error', $message);
            }

            if ($request->has('delink_tracks') && $request->delink_tracks) {
                $skill->tracks()->detach();
            }

            if ($skill->tracks()->count() > 0) {
                $message = 'There are tracks that use this skill. Do you want to delink all the tracks first?';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message, 
                        'code' => 409,
                        'requires_confirmation' => true
                    ], 409);
                }

                return redirect()->back()->with('error', $message);
            }

            // Delete associated files
            if ($skill->image && file_exists(public_path($skill->image))) {
                unlink(public_path($skill->image));
            }

            // Delete associated links and their files
            foreach ($skill->links as $link) {
                if ($link->link && file_exists(public_path($link->link))) {
                    unlink(public_path($link->link));
                }
                $link->delete();
            }

            $skill->delete();
            DB::commit();
// After successful delete
            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Skill has been deleted successfully.',
                    'redirect' => route('admin.skills.index'), 
                    'code'     => 200
                ]);
            }
            return redirect()->route('admin.skills.index')->with('success', 'Skill deleted successfully.');


        } catch (\Exception $e) {
            DB::rollback();


            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting skill: ' . $e->getMessage(),
                    'code' => 500
                ], 500);
            }

            return redirect()->back()
            ->with('error', 'Error deleting skill: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a skill
     */
    public function duplicate(Skill $skill)
    {
        try {
            $newSkill = $skill->replicate();
            $newSkill->skill = $skill->skill . ' (Copy)';
            $newSkill->user_id = Auth::id();

            if ($newSkill->save()) {
            // For AJAX requests, return JSON with redirect instruction
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Skill duplicated successfully',
                        'skill_id' => $newSkill->id,
                        'redirect' => route('admin.skills.show', $newSkill->id)
                    ]);
                }

            // For regular form submissions, redirect normally
                return redirect()->route('admin.skills.show', $newSkill->id)
                ->with('success', 'Skill duplicated successfully');
            } else {
                throw new \Exception('Failed to save skill');
            }
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error duplicating skill: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
            ->with('error', 'Error duplicating skill: ' . $e->getMessage());
        }
    }
    /**
     * Search skills by track, level, or keyword
     */
    public function search(Request $request)
    {
        $skills = collect();
        
        if ($request->track) {
            $skills = Cache::remember('skills_track_'.$request->track, 15/60, function() use ($request) {
                return Track::find($request->track)->skills()->with('questions','tracks','users')->get();
            });
        }
        
        if ($request->level) {
            $skills = Cache::remember('skills_level_'.$request->level, 15/60, function() use ($request) {
                return Skill::with('questions','tracks','users')->whereHas('tracks', function ($query) use ($request) {
                    $query->whereIn('id', Level::find($request->level)->tracks()->pluck('id')->toArray());
                })->get();
            });
        }
        
        if ($request->keyword) {
            $skills = Cache::remember('skills_keyword_'.md5($request->keyword), 15/60, function() use ($request) {
                return Skill::with('questions','tracks','users')->where('description','LIKE','%'.$request->keyword.'%')->get();
            });
        }

        return response()->json(['skills' => $skills], 200);
    }

    /**
     * Add a track to a skill
     */
    public function addTrack(Request $request, $id)
    {
        $user = Auth::user();
        $skill = Skill::findOrFail($id);
        
        if ($user->id != $skill->user_id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized', 'code' => 403], 403);
        }
        
        $request->validate(['track_id' => 'required|exists:tracks,id']);
        
        try {
            $skill->tracks()->syncWithoutDetaching([$request->track_id]);
            $skill->load(['tracks.level']);
            
            return response()->json([
                'success' => true,
                'message' => 'Track added successfully',
                'skill' => $skill,
                'code' => 200
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding track: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    /**
     * Remove a track from a skill
     */
    public function removeTrack($skillId, $trackId)
    {
        $user = Auth::user();
        $skill = Skill::findOrFail($skillId);
        
        if ($user->id != $skill->user_id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized', 'code' => 403], 403);
        }
        
        try {
            $skill->tracks()->detach($trackId);
            $skill->load(['tracks.level']);
            
            return response()->json([
                'success' => true,
                'message' => 'Track removed successfully',
                'skill' => $skill,
                'code' => 200
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing track: ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    /**
     * Get skill data for AJAX requests
     */
    public function getSkillData(Skill $skill)
    {
        try {
            $skill->load(['tracks.level', 'questions', 'user']);
            
            $questionTypes = Type::orderBy('type')->get(['id', 'type', 'description']);
            $difficulties = \App\Models\Difficulty::orderBy('id')->get(['id', 'difficulty', 'short_description']);
            
            $skillData = [
                'id' => $skill->id,
                'skill' => $skill->skill,
                'description' => $skill->description,
                'questions_count' => $skill->questions->count(),
                'user' => $skill->user ? [
                    'name' => $skill->user->name,
                    'email' => $skill->user->email
                ] : null
            ];
            
            return response()->json([
                'success' => true,
                'skill' => $skillData,
                'question_types' => $questionTypes,
                'difficulties' => $difficulties,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading skill data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get similar skills for copying questions
     */
    public function getSimilarSkills(Skill $skill)
    {
        try {
            $trackIds = $skill->tracks->pluck('id');
            
            if ($trackIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'skills' => [],
                    'message' => 'No similar skills found (current skill has no tracks assigned)'
                ]);
            }
            
            $similarSkills = Skill::whereHas('tracks', function($query) use ($trackIds) {
                $query->whereIn('id', $trackIds);
            })
            ->where('id', '!=', $skill->id)
            ->withCount('questions')
            ->having('questions_count', '>', 0)
            ->orderByDesc('questions_count')
            ->limit(15)
            ->get(['id', 'skill', 'description'])
            ->map(function($skill) {
                return [
                    'id' => $skill->id,
                    'skill' => $skill->skill,
                    'description' => $skill->description,
                    'questions_count' => $skill->questions_count
                ];
            });

            return response()->json([
                'success' => true,
                'skills' => $similarSkills
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading similar skills: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get skills available for copying questions
     */
    public function getSkillsForCopy($id)
    {
        $skill = Skill::findOrFail($id);
        
        $skills = Skill::where('id', '!=', $skill->id)
        ->whereHas('questions')
        ->with(['questions' => function($query) {
            $query->select('id', 'skill_id', 'question');
        }])
        ->withCount('questions')
        ->orderBy('skill')
        ->get();

        return response()->json([
            'success' => true,
            'skills' => $skills,
            'code' => 200
        ]);
    }

    /**
     * Get users who passed/attempted/failed this skill
     */
    public function usersPassed($id) 
    {
        $skill = Skill::findOrFail($id);
        
        return response()->json([
            'message' => 'Users who passed/attempted/failed this skill.',
            'passed' => $skill->users()->wherePivot('skill_passed','=', true)->get(),
            'failed' => $skill->users()->wherePivot('skill_passed','=', false)->wherePivot('noOfFails','<', 4)->get(),
            'attempted' => $skill->users()->wherePivot('skill_passed','=', false)->wherePivot('noOfFails','<', 4)->get(),
            'code' => 200
        ]);
    }

    // =====================================================
    // LEGACY METHODS - TO BE MOVED TO QuestionController
    // =====================================================
    
    /**
     * @deprecated - Move to QuestionController
     */
    public function bulkAddQuestions(Request $request, $id)
    {
        // This method should be moved to QuestionController
        // Keeping for backward compatibility
        return response()->json([
            'success' => false,
            'message' => 'This method has been moved to QuestionController. Please use the new endpoint.'
        ], 410);
    }

    /**
     * @deprecated - Move to QuestionController
     */
    public function importQuestions(Request $request, $id)
    {
        // This method should be moved to QuestionController
        // Keeping for backward compatibility
        return response()->json([
            'success' => false,
            'message' => 'This method has been moved to QuestionController. Please use the new endpoint.'
        ], 410);
    }

    /**
     * @deprecated - Move to QuestionController
     */
    public function copyQuestions(Request $request, $id)
    {
        // This method should be moved to QuestionController
        // Keeping for backward compatibility
        return response()->json([
            'success' => false,
            'message' => 'This method has been moved to QuestionController. Please use the new endpoint.'
        ], 410);
    }

    /**
     * @deprecated - Move to QuestionController
     */
    public function questionsDuplicate(Skill $skill)
    {
        // This method should be moved to QuestionController
        return redirect()->route('admin.skills.questions.generate.form', $skill)
        ->with('info', 'Question management has been moved to a dedicated section.');
    }

    /**
     * @deprecated - Move to QuestionController
     */
    public function processQuestionsDuplicate(Request $request, Skill $skill)
    {
        // This method should be moved to QuestionController
        return response()->json([
            'success' => false,
            'message' => 'This method has been moved to QuestionController. Please use the new endpoint.'
        ], 410);
    }
    public function linkVideo(Request $request, Skill $skill)
    {
        $request->validate([
            'video_path' => 'required|string',
            'title' => 'required|string|max:255'
        ]);
        
        SkillLink::create([
            'skill_id' => $skill->id,
            'user_id' => Auth::id(),
            'link' => $request->video_path,
            'title' => $request->title,
            'status_id' => 3
        ]);
        
        return response()->json([
            'success' => true,  // Add this line
            'message' => 'Video linked successfully'
        ]);
    }

    public function deleteVideo($skill, $video)
    {
        try {
        // Find the skilllink record
            $skillLink = SkillLink::where('id', $video)
            ->where('skill_id', $skill)
            ->firstOrFail();

        // Store info before deletion for logging
            $recordInfo = $skillLink->toArray();

        // Delete the record
            $deleted = $skillLink->delete();

        // Check if delete was successful
            if (!$deleted) {
                return response()->json([
                    'error' => 'Failed to delete record'
                ], 500);
            }

        // Verify the record is gone
            $stillExists = SkillLink::where('id', $video)->exists();

            return response()->json([
                'message' => 'Video removed from skill successfully',
                'deleted' => $deleted,
                'still_exists' => $stillExists,
                'deleted_record' => $recordInfo
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Video link not found for this skill'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to remove video from skill',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}