<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\Skill;
use App\Models\Track;
use App\Models\Question;
use App\Models\Status;
use App\Models\Level;
use App\Models\Type;
use App\Models\Video;

class SkillController extends Controller
{
    /**
     * Admin listing (JSON for AJAX) or Blade (webIndex)
     */
    public function index(Request $request)
    {
        if (!($request->ajax() && $request->expectsJson())) {
            return $this->webIndex($request);
        }

        $query = Skill::with(['tracks.level', 'status', 'questions', 'videos'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->filled('track_id')) {
            $query->whereHas('tracks', function ($q) use ($request) {
                $q->where('tracks.id', $request->track_id);
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('skill', 'LIKE', "%{$s}%")
                  ->orWhere('description', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('sort')) {
            $query->orderBy($request->sort, $request->input('direction', 'asc'));
        }

        $skills = $query->get();

        $skills->each(function ($skill) {
            $skill->tracks_count = $skill->tracks()->count();
        });

        return response()->json([
            'skills' => $skills,
            'totals' => [
                'total'   => $skills->count(),
                'public'  => $skills->where('status.status', 'Public')->count(),
                'draft'   => $skills->where('status.status', 'Draft')->count(),
                'private' => $skills->where('status.status', 'Only Me')->count(),
            ],
            'num_pages' => 1,
        ]);
    }

    /**
     * Blade index
     */
    private function webIndex(Request $request)
    {
        $skills = Skill::with([
            'videos',
            'tracks.level',
            'user',
            'status',
            'questions' => fn($q) => $q->select('id','skill_id','qa_status','created_at'),
        ])
        ->withCount('questions')
        ->orderBy('created_at','desc')
        ->get();

        $skills->each(function ($skill) {
            $skill->tracks_count = $skill->tracks()->count();
        });

        return view('admin.skills.index', compact('skills'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!request()->expectsJson()) {
            $statuses = Status::all();
            $tracks   = Track::with('level')->get();
            return view('admin.skills.create', compact('statuses','tracks'));
        }

        if (!$user->is_admin) {
            return response()->json(['message' => 'Only administrators can create a new skill.','code'=>403], 403);
        }

        return response()->json([
            'message'        => 'Skill create form data.',
            'statuses'       => Status::all(),
            'my_tracks'      => $user->tracks,
            'public_tracks'  => Track::all(),
            'code'           => 200
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $msg = 'Only administrators can create new skills';
            return $request->expectsJson()
                ? response()->json(['message'=>$msg,'code'=>403],403)
                : redirect()->back()->with('error',$msg);
        }

        $request->validate([
            'skill'       => 'required|string|max:255',
            'description' => 'required|string',
            'status_id'   => 'required|exists:statuses,id',
            'track_ids'   => 'array',
            'track_ids.*' => 'exists:tracks,id',
            // image can be either uploaded or just a saved path
            'image'       => 'nullable|string|max:255',
            'image_file'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $values = $request->except(['track_ids','image_file']);
            $values['user_id'] = $user->id;

            // uploaded image (takes precedence)
            if ($request->hasFile('image_file')) {
                $image = $request->file('image_file');
                $name  = time().'.'.$image->getClientOriginalExtension();
                $image->move(public_path('images/skills'), $name);
                $values['image'] = 'images/skills/'.$name;
            }

            $skill = Skill::create($values);

            if ($request->filled('track_ids')) {
                $ids = is_string($request->track_ids) ? json_decode($request->track_ids,true) : $request->track_ids;
                $skill->tracks()->sync($ids ?: []);
            }

            DB::commit();

            $skill->load(['tracks.level','user','videos']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Skill created successfully.',
                    'skill'   => $skill,
                    'code'    => 201
                ], 201);
            }

            return redirect()->route('admin.skills.index')->with('success','Skill created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = 'Error creating skill: '.$e->getMessage();
            return $request->expectsJson()
                ? response()->json(['message'=>$msg,'code'=>500],500)
                : redirect()->back()->with('error',$msg)->withInput();
        }
    }

    public function show(Skill $skill)
    {
        $skill->load(['tracks.level','user','questions.difficulty','questions.type','videos']);

        $statuses      = Status::select('id','status','description')->get();
        $difficulties  = \App\Models\Difficulty::select('id','short_description','description')->get();
        $questionTypes = Type::select('id','type','description')->get();
        $skills        = Skill::select('id','skill')->orderBy('skill')->get();

        if (request()->expectsJson()) {
            return response()->json([
                'message'       => 'Skill fetched successfully.',
                'skill'         => $skill,
                'statuses'      => $statuses,
                'difficulties'  => $difficulties,
                'question_types'=> $questionTypes,
                'skills'        => $skills,
                'code'          => 200
            ]);
        }

        return view('admin.skills.show', compact('skill','statuses','difficulties','questionTypes','skills'));
    }

    public function edit(Skill $skill)
    {
        $user = Auth::user();
        if ($user->id != $skill->user_id && !$user->is_admin) {
            $msg = 'You have no access rights to edit this skill';
            return request()->expectsJson()
                ? response()->json(['message'=>$msg,'code'=>403],403)
                : redirect()->back()->with('error',$msg);
        }

        $skill->load(['tracks.level','user','videos']);
        $statuses = Status::all();
        $tracks   = Track::with('level')->get();

        if (request()->expectsJson()) {
            return response()->json([
                'message'  => 'Skill edit form data.',
                'skill'    => $skill,
                'statuses' => $statuses,
                'tracks'   => $tracks,
                'code'     => 200
            ]);
        }

        return view('admin.skills.edit', compact('skill','statuses','tracks'));
    }

    public function update(Request $request, Skill $skill)
    {
        // Inline "field/value" updates
        if ($request->has('field') && $request->has('value') && $request->expectsJson()) {
            return $this->handleInlineUpdate($request, $skill);
        }

        $request->validate([
            'skill'         => 'sometimes|string|max:255',
            'description'   => 'sometimes|nullable|string',
            'status_id'     => 'sometimes|exists:statuses,id',
            // string path from picker
            'image'         => 'nullable|string|max:255',
            // explicit file upload
            'image_file'    => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            // tracks sync
            'track_ids'     => 'sometimes|array',
            'track_ids.*'   => 'exists:tracks,id',
        ]);

        DB::beginTransaction();
        try {
            // uploaded image has priority
            if ($request->hasFile('image_file')) {
                if ($skill->image && file_exists(public_path($skill->image))) {
                    @unlink(public_path($skill->image));
                }
                $image = $request->file('image_file');
                $name  = time().'.'.$image->getClientOriginalExtension();
                $image->move(public_path('images/skills'), $name);
                $skill->image = 'images/skills/'.$name;
            }
            // picker path (string)
            if ($request->filled('image') && is_string($request->image)) {
                $skill->image = $request->image;
            }

            // tracks
            if ($request->has('track_ids')) {
                $ids = is_string($request->track_ids) ? json_decode($request->track_ids,true) : $request->track_ids;
                $skill->tracks()->sync($ids ?: []);
            }

            // fill remaining
            $skill->fill($request->except(['track_ids','image_file']))->save();

            DB::commit();

            $skill->load(['tracks.level','user','videos']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Skill updated successfully',
                    'skill'   => $skill,
                    'code'    => 200
                ]);
            }

            return redirect()->route('admin.skills.index')->with('success','Skill updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = 'Error updating skill: '.$e->getMessage();
            return $request->expectsJson()
                ? response()->json(['message'=>$msg,'code'=>500],500)
                : redirect()->back()->with('error',$msg)->withInput();
        }
    }

    private function handleInlineUpdate(Request $request, Skill $skill)
    {
        $field = $request->input('field');
        $value = $request->input('value');
        $allowed = ['skill','description','status_id','check','image']; // allow image inline

        if (!in_array($field, $allowed)) {
            return response()->json(['success'=>false,'message'=>'Invalid field'],400);
        }

        try {
            $skill->update([$field => $value]);
            return response()->json(['success'=>true,'message'=>'Updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()],500);
        }
    }

    public function destroy(Request $request, Skill $skill)
    {
        $user = Auth::user();
        if ($user->id != $skill->user_id && !$user->is_admin) {
            $msg = 'You have no access rights to delete this skill';
            return $request->expectsJson()
                ? response()->json(['success'=>false,'message'=>$msg,'code'=>403],403)
                : redirect()->back()->with('error',$msg);
        }

        DB::beginTransaction();
        try {
            if ($skill->questions()->count() > 0) {
                $msg = 'There are questions in this skill. Delete all questions first.';
                return $request->expectsJson()
                    ? response()->json(['success'=>false,'message'=>$msg,'code'=>409],409)
                    : redirect()->back()->with('error',$msg);
            }

            if ($request->boolean('delink_tracks')) {
                $skill->tracks()->detach();
            }

            if ($skill->tracks()->count() > 0) {
                $msg = 'There are tracks that use this skill. Do you want to delink all the tracks first?';
                return $request->expectsJson()
                    ? response()->json(['success'=>false,'message'=>$msg,'code'=>409,'requires_confirmation'=>true],409)
                    : redirect()->back()->with('error',$msg);
            }

            if ($skill->image && file_exists(public_path($skill->image))) {
                @unlink(public_path($skill->image));
            }

            // videos pivot rows will cascade via FK if set; otherwise detach:
            $skill->videos()->detach();

            $skill->delete();
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Skill has been deleted successfully.',
                    'redirect' => route('admin.skills.index'),
                    'code'     => 200
                ]);
            }
            return redirect()->route('admin.skills.index')->with('success','Skill deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = 'Error deleting skill: '.$e->getMessage();
            return $request->expectsJson()
                ? response()->json(['success'=>false,'message'=>$msg,'code'=>500],500)
                : redirect()->back()->with('error',$msg);
        }
    }

    public function duplicate(Skill $skill)
    {
        try {
            $new = $skill->replicate();
            $new->skill  = $skill->skill.' (Copy)';
            $new->user_id = Auth::id();

            if ($new->save()) {
                return request()->expectsJson()
                    ? response()->json(['success'=>true,'message'=>'Skill duplicated successfully','skill_id'=>$new->id,'redirect'=>route('admin.skills.show',$new->id)])
                    : redirect()->route('admin.skills.show',$new->id)->with('success','Skill duplicated successfully');
            }
            throw new \Exception('Failed to save skill');
        } catch (\Exception $e) {
            $msg = 'Error duplicating skill: '.$e->getMessage();
            return request()->expectsJson()
                ? response()->json(['success'=>false,'message'=>$msg],500)
                : redirect()->back()->with('error',$msg);
        }
    }

    public function search(Request $request)
    {
        $skills = collect();

        if ($request->track) {
            $skills = Cache::remember('skills_track_'.$request->track, 15/60, function () use ($request) {
                return Track::find($request->track)->skills()->with('questions','tracks','users')->get();
            });
        }

        if ($request->level) {
            $skills = Cache::remember('skills_level_'.$request->level, 15/60, function () use ($request) {
                return Skill::with('questions','tracks','users')->whereHas('tracks', function ($q) use ($request) {
                    $q->whereIn('id', Level::find($request->level)->tracks()->pluck('id')->toArray());
                })->get();
            });
        }

        if ($request->keyword) {
            $skills = Cache::remember('skills_keyword_'.md5($request->keyword), 15/60, function () use ($request) {
                return Skill::with('questions','tracks','users')->where('description','LIKE','%'.$request->keyword.'%')->get();
            });
        }

        return response()->json(['skills'=>$skills],200);
    }

    public function addTrack(Request $request, $id)
    {
        $user  = Auth::user();
        $skill = Skill::findOrFail($id);
        if ($user->id != $skill->user_id && !$user->is_admin) {
            return response()->json(['message'=>'Unauthorized','code'=>403],403);
        }

        $request->validate(['track_id'=>'required|exists:tracks,id']);
        try {
            $skill->tracks()->syncWithoutDetaching([$request->track_id]);
            $skill->load(['tracks.level']);
            return response()->json(['success'=>true,'message'=>'Track added successfully','skill'=>$skill,'code'=>200]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Error adding track: '.$e->getMessage(),'code'=>500],500);
        }
    }

    public function removeTrack($skillId, $trackId)
    {
        $user  = Auth::user();
        $skill = Skill::findOrFail($skillId);
        if ($user->id != $skill->user_id && !$user->is_admin) {
            return response()->json(['message'=>'Unauthorized','code'=>403],403);
        }

        try {
            $skill->tracks()->detach($trackId);
            $skill->load(['tracks.level']);
            return response()->json(['success'=>true,'message'=>'Track removed successfully','skill'=>$skill,'code'=>200]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Error removing track: '.$e->getMessage(),'code'=>500],500);
        }
    }

    public function getSkillData(Skill $skill)
    {
        try {
            $skill->load(['tracks.level','questions','user']);
            $questionTypes = Type::orderBy('type')->get(['id','type','description']);
            $difficulties  = \App\Models\Difficulty::orderBy('id')->get(['id','difficulty','short_description']);

            return response()->json([
                'success' => true,
                'skill'   => [
                    'id'              => $skill->id,
                    'skill'           => $skill->skill,
                    'description'     => $skill->description,
                    'questions_count' => $skill->questions->count(),
                    'user'            => $skill->user ? ['name'=>$skill->user->name,'email'=>$skill->user->email] : null,
                ],
                'question_types' => $questionTypes,
                'difficulties'   => $difficulties,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Error loading skill data: '.$e->getMessage()],500);
        }
    }

    public function getSimilarSkills(Skill $skill)
    {
        try {
            $trackIds = $skill->tracks->pluck('id');
            if ($trackIds->isEmpty()) {
                return response()->json(['success'=>true,'skills'=>[],'message'=>'No similar skills found (current skill has no tracks assigned)']);
            }

            $similar = Skill::whereHas('tracks', fn($q)=>$q->whereIn('id',$trackIds))
                ->where('id','!=',$skill->id)
                ->withCount('questions')
                ->having('questions_count','>',0)
                ->orderByDesc('questions_count')
                ->limit(15)
                ->get(['id','skill','description'])
                ->map(fn($s)=>['id'=>$s->id,'skill'=>$s->skill,'description'=>$s->description,'questions_count'=>$s->questions_count]);

            return response()->json(['success'=>true,'skills'=>$similar]);
        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>'Error loading similar skills: '.$e->getMessage()],500);
        }
    }

    public function getSkillsForCopy($id)
    {
        $skill = Skill::findOrFail($id);
        $skills = Skill::where('id','!=',$skill->id)
            ->whereHas('questions')
            ->with(['questions'=>fn($q)=>$q->select('id','skill_id','question')])
            ->withCount('questions')
            ->orderBy('skill')
            ->get();

        return response()->json(['success'=>true,'skills'=>$skills,'code'=>200]);
    }

    public function usersPassed($id)
    {
        $skill = Skill::findOrFail($id);
        return response()->json([
            'message'   => 'Users who passed/attempted/failed this skill.',
            'passed'    => $skill->users()->wherePivot('skill_passed',true)->get(),
            'failed'    => $skill->users()->wherePivot('skill_passed',false)->wherePivot('noOfFails','<',4)->get(),
            'attempted' => $skill->users()->wherePivot('skill_passed',false)->wherePivot('noOfFails','<',4)->get(),
            'code'      => 200
        ]);
    }

    // ----------------------------
    // Video linking (pivot)
    // ----------------------------
    public function linkVideo(Request $request, Skill $skill)
    {
        $data = $request->validate([
            'video_id'   => ['required','integer','exists:videos,id'],
            'status_id'  => ['required','integer','exists:statuses,id'],
            'sort_order' => ['nullable','integer'],
        ]);

        $skill->videos()->syncWithoutDetaching([
            $data['video_id'] => [
                'status_id'  => $data['status_id'],
                'sort_order' => $data['sort_order'] ?? null,
            ],
        ]);

        return response()->json(['success'=>true]);
    }

    public function deleteVideo(Skill $skill, Video $video)
    {
        $skill->videos()->detach($video->id);
        return response()->json(['success'=>true]);
    }

    /**
     * DB-backed video search for the picker
     * GET /admin/videos/search?q=&field_id=&per_page=
     */
    public function videosSearch(Request $request)
    {
        $q   = trim((string)$request->input('q',''));
        $per = max(1, min((int)$request->input('per_page', 100), 200));

        $query = Video::query();
        if ($q !== '') {
            $query->where(function ($s) use ($q) {
                $s->where('video_title','like',"%{$q}%")
                  ->orWhere('video_link','like',"%{$q}%");
            });
        }
        if ($request->filled('field_id')) {
            $query->where('field_id', (int)$request->field_id);
        }

        $videos = $query->orderByRaw("COALESCE(NULLIF(video_title,''), video_link)")
            ->limit($per)
            ->get()
            ->map(function ($v) {
                return [
                    'id'     => $v->id,
                    'title'  => $v->video_title ?: basename($v->video_link),
                    'path'   => $v->video_link,
                    'field_id' => $v->field_id,
                    'url'    => Storage::disk('webroot')->url($v->video_link),
                ];
            });

        return response()->json(['videos'=>$videos]);
    }
}
