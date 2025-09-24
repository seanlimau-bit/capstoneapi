<?php
namespace App\Http\Controllers;

class EmergencyController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_questions' => 100,
            'active_users' => 50,
            'pending_qa' => 10,
            'total_skills' => 25,
        ];
        
        // Use the emergency dashboard view, not the original admin.dashboard
        return view('emergency-dashboard', compact('stats'));
    }
}