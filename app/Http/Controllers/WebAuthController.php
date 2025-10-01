<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendOtpMail;
use App\Models\User;
use App\Models\Role;
use Twilio\Rest\Client;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showVerify(Request $request)
    {
        if (!$request->has('identifier')) {
            return redirect()->route('login');
        }
        return view('auth.verify', [
            'identifier' => $request->identifier,
            'channel' => $request->channel
        ]);
    }

public function sendOtp(Request $request)
{
    \Log::info('=== SendOTP Started ===');
    \Log::info('Request data:', $request->all());

    try {
        $request->validate([
            'identifier' => 'required|string',
            'channel' => 'required|in:email,sms,whatsapp'
        ]);
        \Log::info('Validation passed');

        $identifier = $request->identifier;
        $channel = $request->channel;
        \Log::info("Identifier: {$identifier}, Channel: {$channel}");

        // Validate identifier based on channel
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        \Log::info('Is email: ' . ($isEmail ? 'yes' : 'no'));

        if ($channel === 'email' && !$isEmail) {
            \Log::warning('Email required but not valid');
            return back()->withInput()->with('error', 'Please provide a valid email address');
        }

        if (in_array($channel, ['sms', 'whatsapp']) && $isEmail) {
            \Log::warning('Phone required but email provided');
            return back()->withInput()->with('error', 'Please provide a phone number for SMS/WhatsApp');
        }

        // Find or create user
        \Log::info('Finding or creating user...');
        $user = User::firstOrCreate(
            $isEmail ? ['email' => $identifier] : ['phone_number' => $identifier],
            [
                'status' => 'active',
                'access_type' => 'free',
                'signup_channel' => 'direct',
                'role_id' => 6,
                'diagnostic' => true,
                'payment_method' => 'free',
            ]
        );
        \Log::info('User found/created: ' . $user->id);

        // Generate OTP
        $otp = rand(100000, 999999);
        \Log::info("Generated OTP: {$otp}");

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);
        \Log::info('OTP saved to database');

        // Send OTP
        \Log::info("Attempting to send via {$channel}...");
        switch ($channel) {
            case 'email':
                Mail::to($user->email)->send(new SendOtpMail($otp));
                \Log::info('Email sent successfully');
                break;

            case 'sms':
                $this->sendSmsOtp($user->phone_number, $otp);
                \Log::info('SMS sent successfully');
                break;

            case 'whatsapp':
                $this->sendWhatsAppOtp($user->phone_number, $otp);
                \Log::info('WhatsApp sent successfully');
                break;
        }

        \Log::info('Redirecting to verify page...');
        return redirect()->route('auth.verify', [
            'identifier' => $identifier,
            'channel' => $channel
        ])->with('success', 'Verification code sent successfully');

    } catch (\Exception $e) {
        \Log::error('SendOTP Exception: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
    }
}

public function verifyOtp(Request $request)
{
    $request->validate([
        'identifier' => 'required|string',
        'otp_code' => 'required|string|size:6'
    ]);
    
    $isEmail = filter_var($request->identifier, FILTER_VALIDATE_EMAIL);
    $user = User::where($isEmail ? 'email' : 'phone_number', $request->identifier)
        ->where('otp_code', $request->otp_code)
        ->where('otp_expires_at', '>', now())
        ->first();
        
    if (!$user) {
        return back()
            ->withErrors(['otp_code' => 'Invalid or expired verification code'])
            ->withInput(['identifier' => $request->identifier]);
    }
    
    // Check if this is a new user (no role assigned yet)
    $isNewUser = !$user->role_id;
    
    if ($isNewUser) {
        // Assign default role for new users - use a basic "Pending" or "Student" role
        $defaultRoleId = Role::where('role', 'LIKE', '%Student%')->first()?->id ?? 6;
        
        $user->update([
            'role_id' => $defaultRoleId,
            'status' => 'pending', // Not 'active' until admin approves
        ]);
    }
    
    // Mark verified and clear OTP
    $user->update([
        'email_verified' => true,
        'email_verified_at' => now(),
        'activated_at' => $user->activated_at ?? now(),
        'otp_code' => null,
        'otp_expires_at' => null
    ]);
    
    Auth::login($user);
    
    // Route based on permissions
    if ($user->canAccessAdmin()) {
        return redirect()->route('admin.dashboard.index');
    }
    
    if ($user->canAccessQA()) {
        return redirect()->route('admin.qa.index');
    }
    
    // New users without admin/QA access - show awaiting access page
    return redirect()->route('auth.awaiting-access');
}
    private function sendSmsOtp($phoneNumber, $otp)
    {
        $twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilio->messages->create($phoneNumber, [
            'from' => config('services.twilio.from'),
            'body' => "Your All Gifted verification code is: {$otp}. Valid for 10 minutes."
        ]);
    }

    private function sendWhatsAppOtp($phoneNumber, $otp)
    {
        $twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        // WhatsApp requires 'whatsapp:' prefix for both from and to
        $twilio->messages->create("whatsapp:{$phoneNumber}", [
            'from' => 'whatsapp:' . config('services.twilio.whatsapp'),
            'body' => "Your All Gifted verification code is: {$otp}\n\nValid for 10 minutes."
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}