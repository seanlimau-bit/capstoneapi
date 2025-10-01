<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Field;
use App\Models\Track;
use App\Models\Skill;
use App\Models\Question;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        return $this->dashboard();
    }
    
    public function dashboard()
    {
        // Cache dashboard data for 5 minutes to improve performance
        $data = Cache::remember('admin_dashboard', 300, function () {
            return [
                'counts' => $this->getCounts(),
                'metrics' => $this->getMetrics(),
                'insights' => $this->getInsights(),
                'recent_questions' => $this->getRecentQuestions(),
                'chart_data' => $this->getChartData(),
                'field_distribution' => $this->getFieldDistribution(),
            ];
        });

        return view('admin.dashboard.index', $data);
    }

    /**
     * Get all entity counts
     */
    private function getCounts(): array
    {
        return [
            // Hierarchy counts
            'fields' => Field::count(),
            'tracks' => Track::count(),
            'skills' => Skill::count(),
            'questions' => Question::count(),
            
            // User metrics
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            
            // QA status breakdown
            'pending_qa' => Question::where('qa_status', 'unreviewed')->count(),
            'unreviewed' => Question::where('qa_status', 'unreviewed')->count(),
            'approved' => Question::where('qa_status', 'approved')->count(),
            'flagged' => Question::where('qa_status', 'flagged')->count(),
            'needs_revision' => Question::where('qa_status', 'needs_revision')->count(),
        ];
    }

    /**
     * Get calculated metrics and growth rates
     */
    private function getMetrics(): array
    {
        $totalQuestions = Question::count();
        $approvedQuestions = Question::where('qa_status', 'approved')->count();
        
        // QA approval rate
        $qaApprovalRate = $totalQuestions > 0 
            ? ($approvedQuestions / $totalQuestions) * 100 
            : 0;

        // User growth rate (last 30 days vs previous 30 days)
        $usersLast30 = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $usersPrevious30 = User::whereBetween('created_at', [
            Carbon::now()->subDays(60),
            Carbon::now()->subDays(30)
        ])->count();
        
        $userGrowthRate = $usersPrevious30 > 0
            ? (($usersLast30 - $usersPrevious30) / $usersPrevious30) * 100
            : ($usersLast30 > 0 ? 100 : 0);

        // Content growth rate (questions in last 30 days)
        $questionsLast30 = Question::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $questionsPrevious30 = Question::whereBetween('created_at', [
            Carbon::now()->subDays(60),
            Carbon::now()->subDays(30)
        ])->count();
        
        $contentGrowthRate = $questionsPrevious30 > 0
            ? (($questionsLast30 - $questionsPrevious30) / $questionsPrevious30) * 100
            : ($questionsLast30 > 0 ? 100 : 0);

        return [
            'qa_approval_rate' => round($qaApprovalRate, 2),
            'user_growth_rate' => round($userGrowthRate, 2),
            'content_growth_rate' => round($contentGrowthRate, 2),
            'questions_last_30days' => $questionsLast30,
        ];
    }

    /**
     * Get platform insights
     */
    private function getInsights(): array
    {
        // Find top performing track (highest approval rate with significant content)
        // Using the skill_track pivot table for many-to-many relationship
        $topTrack = DB::table('tracks')
            ->leftJoin('skill_track', 'tracks.id', '=', 'skill_track.track_id')
            ->leftJoin('skills', 'skill_track.skill_id', '=', 'skills.id')
            ->leftJoin('questions', 'skills.id', '=', 'questions.skill_id')
            ->select(
                'tracks.id',
                'tracks.track as name',
                DB::raw('COUNT(DISTINCT questions.id) as question_count'),
                DB::raw('SUM(CASE WHEN questions.qa_status = "approved" THEN 1 ELSE 0 END) as approved_count')
            )
            ->groupBy('tracks.id', 'tracks.track')
            ->having('question_count', '>=', 20) // Minimum threshold
            ->get()
            ->map(function ($track) {
                $track->approval_rate = $track->question_count > 0
                    ? ($track->approved_count / $track->question_count) * 100
                    : 0;
                return $track;
            })
            ->sortByDesc('approval_rate')
            ->first();

        return [
            'top_track' => $topTrack ? [
                'name' => $topTrack->name,
                'question_count' => $topTrack->question_count,
                'approval_rate' => round($topTrack->approval_rate, 2),
            ] : null,
            'needs_attention' => Question::where('qa_status', 'unreviewed')->count() > 100,
        ];
    }

    /**
     * Get recent questions for activity feed
     */
    private function getRecentQuestions()
    {
        return Question::with('skill')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question' => strip_tags($question->question),
                    'qa_status' => $question->qa_status,
                    'updated_at' => $question->updated_at,
                ];
            });
    }

    /**
     * Get chart data for content creation trends (last 14 days)
     */
    private function getChartData(): array
    {
        $days = 14;
        $labels = [];
        $created = [];
        $approved = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M j');
            
            $created[] = Question::whereDate('created_at', $date)->count();
            $approved[] = Question::whereDate('created_at', $date)
                ->where('qa_status', 'approved')
                ->count();
        }

        return [
            'labels' => $labels,
            'created' => $created,
            'approved' => $approved,
        ];
    }

    /**
     * Get field distribution for pie chart
     */
    private function getFieldDistribution(): array
    {
        // Fields -> Tracks (direct relationship)
        // Tracks -> Skills (many-to-many via skill_track)
        // Skills -> Questions (direct relationship)
        $fieldData = DB::table('fields')
            ->leftJoin('tracks', 'fields.id', '=', 'tracks.field_id')
            ->leftJoin('skill_track', 'tracks.id', '=', 'skill_track.track_id')
            ->leftJoin('skills', 'skill_track.skill_id', '=', 'skills.id')
            ->leftJoin('questions', 'skills.id', '=', 'questions.skill_id')
            ->select(
                'fields.field as name',
                DB::raw('COUNT(DISTINCT questions.id) as question_count')
            )
            ->groupBy('fields.id', 'fields.field')
            ->orderByDesc('question_count')
            ->limit(6)
            ->get();

        return [
            'labels' => $fieldData->pluck('name')->toArray(),
            'data' => $fieldData->pluck('question_count')->toArray(),
        ];
    }

    /**
     * Clear dashboard cache (call this when major changes occur)
     */
    public function clearCache()
    {
        Cache::forget('admin_dashboard');
        return response()->json(['success' => true, 'message' => 'Dashboard cache cleared']);
    }
}