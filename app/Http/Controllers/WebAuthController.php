<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use App\Models\User;

class WebAuthController extends Controller
{
	public function showLogin()
	{
		$questions = collect();
		return view('auth.login');
	}

	public function showVerify(Request $request)
	{
		if (!$request->has('email')) {
			return redirect()->route('auth.login');
		}
		return view('auth.verify', ['email' => $request->email]);
	}

	public function sendOtp(Request $request)
	{
		$request->validate([
			'email' => 'required|email'
		]);

		$user = DB::table('users')->where('email', $request->email)->first();

		if (!$user) {
			return redirect()->back()
			->withInput()
			->with('error', 'No account found with this email address.');
		}

		if (!$user->email_verified) {
			return redirect()->back()
			->withInput()
			->with('error', 'Email address must be verified to login.');
		}

        // Generate OTP
		$otp = rand(100000, 999999);

		try {
            // Save OTP to database
			DB::table('users')
			->where('id', $user->id)
			->update([
				'otp_code' => $otp,
				'otp_expires_at' => now()->addMinutes(10)
			]);

            // Send email
			Mail::to($user->email)->send(new SendOtpMail($otp));

            // Redirect to verification step
			return redirect()->route('auth.verify', ['email' => $request->email])
			->with('success', 'Verification code sent to your email');

		} catch (\Exception $e) {
			\Log::error('Auth OTP Send Error: ' . $e->getMessage());
			return redirect()->back()
			->withInput()
			->with('error', 'Failed to send verification code. Please try again.');
		}
	}

	public function verifyOtp(Request $request)
	{
		$request->validate([
			'email' => 'required|email',
			'otp_code' => 'required|string|size:6'
		]);

    // Find user with valid OTP in one query
		$user = User::where('email', $request->email)
		->where('otp_code', $request->otp_code)
		->where('otp_expires_at', '>', now())
		->first();

		if (!$user) {
			return redirect()->back()
			->withErrors(['otp_code' => 'Invalid or expired OTP code'])
			->withInput(['email' => $request->email]);
		}

    // Clear the OTP
		$user->update([
			'otp_code' => null,
			'otp_expires_at' => null
		]);

    // Log the user in
		Auth::login($user);

    // Store global admin data in session
		session(['globalAdminData' => $this->getGlobalAdminData()]);

    // Route based on user permissions
		if ($user->canAccessAdmin()) {
			return redirect()->route('admin.dashboard.index')
			->with('success', 'Welcome to the admin dashboard');
		}

		if ($user->canAccessQA()) {
			return redirect()->route('qa.dashboard.index')
			->with('success', 'Welcome to the QA dashboard');
		}

		return redirect()->route('user.dashboard')
		->with('success', 'Welcome back!');
	}
private function getGlobalAdminData()
{
    // Get the ID of the "Public" status
    $publicStatusId = \App\Models\Status::where('status', 'Public')->value('id');
    
    return [
        'statuses' => \App\Models\Status::orderBy('status')->get(['id', 'status'])->toArray(),
        'qa_statuses' => [
            'unreviewed' => 'Unreviewed',
            'approved' => 'Approved',
            'flagged' => 'Flagged',
            'needs_revision' => 'Needs Revision',
            'ai_generated' => 'AI Generated'
        ],
        // Only show public items for these lookup tables
        'difficulties' => \App\Models\Difficulty::where('status_id', $publicStatusId)
            ->orderBy('difficulty')
            ->get(['id', 'difficulty', 'short_description'])
            ->toArray(),
        'fields' => \App\Models\Field::where('status_id', $publicStatusId)
            ->orderBy('field')
            ->get(['id', 'field'])
            ->toArray(),
        'levels' => \App\Models\Level::where('status_id', $publicStatusId)
            ->orderBy('level')
            ->get(['id', 'level', 'description'])
            ->toArray(),
        'tracks' => \App\Models\Track::where('status_id', $publicStatusId)
            ->orderBy('track')
            ->get(['id', 'track'])
            ->toArray(),
        'skills' => \App\Models\Skill::where('status_id', $publicStatusId)
            ->orderBy('skill')
            ->get(['id', 'skill'])
            ->toArray(),
        'types' => \App\Models\Type::where('status_id', $publicStatusId)
            ->orderBy('type')
            ->get(['id', 'type'])
            ->toArray(),
    ];
}
	public function logout(Request $request)
	{
		Auth::logout();
		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect()->route('login')
		->with('success', 'You have been logged out successfully');
	}
}