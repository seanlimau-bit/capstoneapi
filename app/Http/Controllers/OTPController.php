<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SendOtpMail;
use App\User;
use App\Services\PartnerService;
use App\Models\Partner;

class OTPController extends Controller
{
    protected $partnerService;
    
    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
    }
    
    /**
     * Request OTP - handles initial contact entry
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'contact' => 'required|string'
        ]);

        $contact = $request->contact;
        
        // 1. Check existing users first
        $user = $this->findUserByContact($contact);
        
        if ($user) {
            return $this->handleExistingUser($user);
        }
        
        // 2. For new users, always create basic user and ask for full registration
        $user = User::create([
            'phone_number' => $this->isPhone($contact) ? $contact : null,
            'email' => $this->isEmail($contact) ? $contact : null,
            'status' => 'pending',
            'access_type' => 'free', // Default, will upgrade during registration
            'lives' => 3,
            'signup_channel' => 'direct',
            'name' => 'New User'
        ]);
        
        return response()->json([
            'message' => 'Welcome to All Gifted Math! Please complete your registration.',
            'user_id' => $user->id,
            'next_step' => 'complete_registration'
        ]);
    }
    
    /**
     * Complete registration - collects full details and detects partner eligibility
     */
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone_number' => 'required|string',
            'email' => 'required|email',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'date_of_birth' => 'required|date'
        ]);
        
        $user = User::findOrFail($request->user_id);
        
        // Check for duplicate email
        $existingUser = User::where('email', $request->email)
                           ->where('id', '!=', $user->id)
                           ->first();
        
        if ($existingUser) {
            return response()->json([
                'message' => 'Email already exists. Please use a different email or login.',
                'duplicate_email' => true
            ], 422);
        }
        
        // Update user with complete info
        $user->update([
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'name' => $request->firstname . ' ' . $request->lastname,
            'date_of_birth' => $request->date_of_birth,
            'status' => 'active'
        ]);
        
        // Check for partner eligibility with both email and phone
        $partner = $this->detectPartnerEligibility($request->email, $request->phone_number);
        
        if ($partner) {
            $this->upgradeUserToPartner($user, $partner);
            
            // Send verification email
            $this->sendVerificationEmail($user);
            
            return response()->json([
                'message' => "Registration complete! You have {$partner->name} premium access.",
                'partner_upgraded' => true,
                'partner_name' => $partner->name,
                'user_id' => $user->id,
                'benefits' => $partner->getFormattedBenefits(),
                'requires_email_verification' => true
            ]);
        }
        
        // Send verification email for free users too
        $this->sendVerificationEmail($user);
        
        return response()->json([
            'message' => 'Registration complete. You have free access to get started.',
            'user_id' => $user->id,
            'free_benefits' => [
                '3 lives per day',
                'Basic math content',
                'Essential practice questions'
            ],
            'premium_benefits' => [
                'Unlimited lives',
                'Advanced content',
                'Detailed progress tracking',
                'No advertisements'
            ],
            'requires_email_verification' => true
        ]);
    }
    
    /**
     * Verify email with OTP
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required|string'
        ]);
        
        $user = User::where('email', $request->email)
            ->where('otp_code', $request->verification_code)
            ->where('otp_expires_at', '>', now())
            ->first();
        
        if (!$user) {
            return response()->json(['message' => 'Invalid or expired verification code'], 401);
        }
        
        $user->update([
            'email_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null
        ]);
        
        return response()->json([
            'message' => 'Email verified successfully',
            'user_id' => $user->id
        ]);
    }

    /**
     * Verify OTP for login
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'contact' => 'required|string',
            'otp_code' => 'required|string',
        ]);

        $contact = $request->contact;
        $field = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $user = User::where($field, $contact)
            ->where('otp_code', $request->otp_code)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        // Clear OTP and mark email as verified if applicable
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'email_verified' => $field === 'email' ? true : $user->email_verified
        ]);

        $token = $user->createToken('login')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified. Login successful.',
            'token' => $token,
            'user_id' => $user->id,
            'first_name' => $user->firstname,
            'access_type' => $user->access_type,
            'is_partner_user' => !is_null($user->partner_id),
            'partner_name' => $user->partner->name ?? null,
            'has_unlimited_lives' => $user->hasUnlimitedLives(),
            'current_lives' => $user->getCurrentLives(),
            'maxile_level' => (int) $user->maxile_level,
            'game_level' => (int) $user->game_level,
            'features' => $user->partner_features ?? []
        ]);
    }
    
    /**
     * Find user by phone or email
     */
    private function findUserByContact($contact)
    {
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $contact)->first();
        } else {
            return User::where('phone_number', $contact)->first();
        }
    }
    
    /**
     * Handle existing user OTP request
     */
    private function handleExistingUser($user)
    {
        // Check if user needs profile completion (but allow recently registered users)
        if (!$user->firstname || !$user->date_of_birth) {
            return response()->json([
                'message' => 'Please complete your profile to continue',
                'requires_profile_completion' => true,
                'user_id' => $user->id,
                'is_partner_user' => !is_null($user->partner_id),
                'partner_name' => $user->partner->name ?? null
            ], 422);
        }
        
        // Check if email needs verification
        if (!$user->email_verified) {
            // Send a fresh verification OTP
            $otp = rand(100000, 999999);
            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10)
            ]);
            
            try {
                Mail::to($user->email)->send(new SendOtpMail($otp));
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return response()->json([
                'message' => 'Please verify your email to continue. We\'ve sent a new verification code.',
                'requires_email_verification' => true,
                'email' => $user->email,
                'otp' => $otp // Remove this in production - only for debugging
            ], 422);
        }
        
        // Send login OTP to verified user
        $otp = rand(100000, 999999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);
        
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
            
            return response()->json([
                'message' => 'OTP sent to your email',
                'email_hint' => substr($user->email, 0, 2) . '***@' . explode('@', $user->email)[1]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to send OTP'], 500);
        }
    }
    /**
     * Detect partner eligibility from email and phone
     */
    private function detectPartnerEligibility($email, $phone)
    {
        // Get all active partners
        $partners = Partner::where('is_active', true)->get();
        
        foreach ($partners as $partner) {
            if ($this->isEligibleForPartner($partner, $email, $phone)) {
                return $partner;
            }
        }
        
        return null;
    }
    
    /**
     * Check if user is eligible for specific partner
     */
    private function isEligibleForPartner($partner, $email, $phone)
    {
        switch ($partner->code) {
            case 'allgifted_school':
                $domain = explode('@', $email)[1];
                return str_ends_with($domain, '.edu') || 
                       str_ends_with($domain, '.edu.sg') ||
                       str_ends_with($domain, '.sch.sg');
                
            case 'simba':
                // Verify with SIMBA API if phone is a subscriber
                $subscriberData = $this->partnerService->verifySubscriber($phone, $partner);
                return $subscriberData && ($subscriberData['is_active'] ?? false);
                
            default:
                return false;
        }
    }
    
    /**
     * Upgrade user to partner account
     */
    private function upgradeUserToPartner($user, $partner)
    {
        $user->update([
            'partner_id' => $partner->id,
            'access_type' => $partner->access_type,
            'billing_method' => $partner->billing_method,
            'lives' => $partner->default_lives,
            'partner_features' => $partner->features,
            'partner_verified' => !$partner->verification_required,
            'signup_channel' => 'partner_' . $partner->code
        ]);
        
        // Set trial expiry if applicable
        if ($partner->access_type === 'trial' && $partner->trial_duration_days) {
            $user->trial_expires_at = now()->addDays($partner->trial_duration_days);
            $user->save();
        }
    }
    
    /**
     * Send email verification
     */
    private function sendVerificationEmail($user)
    {
        $otp = rand(100000, 999999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);
        
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if contact is a phone number
     */
    private function isPhone($contact)
    {
        return !filter_var($contact, FILTER_VALIDATE_EMAIL) && 
               preg_match('/^[\+]?[0-9\s\-\(\)]{8,}$/', $contact);
    }
    
    /**
     * Check if contact is an email
     */
    private function isEmail($contact)
    {
        return filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
    }
}