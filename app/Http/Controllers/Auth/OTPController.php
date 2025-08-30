<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\SendOtpMail;
use App\Services\SmsService;
use App\User;

class OTPController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate([
            'contact' => 'required|string'
        ]);

        $contact = $request->contact;
        $user = null;

        // Find user by phone or email
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $contact)->first();
        } else {
            $user = User::where('phone_number', $contact)->first();
        }
        
        // NEW: If user not found, check if they're a telco subscriber
        if (!$user) {
            $telcoUser = $this->checkTelcoSubscriber($contact);
            if ($telcoUser) {
                // Create user with 'potential' status
                $user = User::create([
                    'phone_number' => $telcoUser['phone'],
                    'telco_provider' => $telcoUser['provider'],
                    'telco_subscriber_id' => $telcoUser['subscriber_id'],
                    'status' => 'potential',
                    'email_verified' => false,
                    'name' => 'Telco Subscriber', // Temporary name
                    'signup_channel' => 'telco_otp_login'
                ]);
            } else {
                return response()->json(['message' => 'User not found'], 404);
            }
        }
        
        // NEW: Handle potential telco users
        if ($user->status === 'potential') {
            return $this->handlePotentialTelcoUser($user);
        }
        
        // For first-time users without email verification
        if (!$user->email_verified || !$user->date_of_birth) {
            return response()->json([
                'message' => 'Please complete registration first',
                'requires_registration' => true,
                'user_id' => $user->id
            ], 422);
        }
        
        $otp = rand(100000, 999999);
        
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();
        
        // Always send to verified email
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
            
            return response()->json([
                'message' => 'OTP sent to your email',
                'email_hint' => substr($user->email, 0, 2) . '***@' . explode('@', $user->email)[1]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send OTP'], 500);
        }
    }

    // NEW: Handle telco subscribers who haven't activated yet
    private function handlePotentialTelcoUser($user)
    {
        $otp = rand(100000, 999999);
        
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();
        
        // Send SMS (since they might not have email yet)
        $this->sendSMS($user->phone_number, "Your All Gifted verification code: {$otp}");
        
        $telcoName = ucfirst($user->telco_provider ?? 'telco');
        
        return response()->json([
            'message' => "Activating All Gifted Math Premium on your {$telcoName} bill. Enter the verification code sent to your phone.",
            'is_telco_activation' => true,
            'telco_provider' => $user->telco_provider,
            'phone' => $user->phone_number
        ]);
    }

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

        // Clear the OTP once verified
        $user->otp_code = null;
        $user->otp_expires_at = null;

        // NEW: Handle telco user activation
        if ($user->status === 'potential') {
            return $this->activateTelcoUser($user);
        }

        $user->save();

        // Check enrollment for existing users
        $mathEnrollment = $this->checkMathEnrollment($user);
        $hasAccess = $mathEnrollment && $mathEnrollment->expiry_date >= now()->toDateString();

        $token = $user->createToken('login')->plainTextToken;
        $isSubscriber = !is_null($user->date_of_birth) && !is_null($user->firstname);

        return response()->json([
            'message' => 'OTP verified. Login successful.',
            'token' => $token,
            'user_id' => $user->id,
            'first_name' => $user->firstname,
            'is_subscriber' => $isSubscriber,
            'dob' => $user->date_of_birth,
            'maxile_level' => (int) ($user->maxile_level),
            'game_level' => (int) $user->game_level,
            'lives' => (int) $user->lives,
            'has_math_access' => $hasAccess,
            'telco_provider' => $user->telco_provider,
            'enrollment' => $hasAccess ? [
                'program' => 'K-6 Singapore Math',
                'expiry_date' => $mathEnrollment->expiry_date,
                'progress' => $mathEnrollment->progress
            ] : null
        ]);
    }

    // NEW: Activate telco subscriber and enroll in math program
    private function activateTelcoUser($user)
    {
        try {
            // 1. Activate telco service
            $this->activateTelcoService($user);
            
            // 2. Update user status
            $user->status = 'active';
            $user->payment_method = 'telco_billing';
            $user->activated_at = now();
            $user->email_verified = true; // Skip email verification for telco users
            $user->lives = 5; // Give them starting lives
            $user->save();
            
            // 3. Enroll in K-6 Math program
            $enrollmentId = $this->createMathEnrollment($user);
            
            $token = $user->createToken('login')->plainTextToken;
            
            return response()->json([
                'message' => 'All Gifted Math Premium activated! Welcome!',
                'token' => $token,
                'user_id' => $user->id,
                'first_name' => $user->name,
                'is_subscriber' => true,
                'maxile_level' => (int) $user->maxile_level,
                'game_level' => (int) $user->game_level,
                'lives' => (int) $user->lives,
                'has_math_access' => true,
                'telco_provider' => $user->telco_provider,
                'enrollment' => [
                    'program' => 'K-6 Singapore Math',
                    'billing' => '$3/month via ' . ucfirst($user->telco_provider),
                    'enrollment_id' => $enrollmentId
                ],
                'is_new_activation' => true
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Telco activation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => 'Activation failed. Please try again.'], 500);
        }
    }

    public function completeRegistration(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'date_of_birth' => 'required|date'
        ]);
        
        // Create user with both phone and email
        $user = User::create([
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'name' => $request->firstname . ' ' . $request->lastname,
            'date_of_birth' => $request->date_of_birth,
            'email_verified' => false,
            'status' => 'active', // Default status for regular users
            'lives' => 5, // Starting lives
            'is_admin' => false
        ]);
        
        // Send email verification
        $this->sendEmailVerification($user);
        
        return response()->json([
            'message' => 'Registration complete. Please verify your email.',
            'user_id' => $user->id
        ]);
    }

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
        
        $user->email_verified = true;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();
        
        return response()->json(['message' => 'Email verified successfully']);
    }

    // NEW: Check if contact is a telco subscriber
    private function checkTelcoSubscriber($contact)
    {
        // TODO: Implement actual telco API calls
        // For now, simulate telco check
        if (str_starts_with($contact, '+659')) { // Singapore mobile
            return [
                'phone' => $contact,
                'provider' => 'simba', // or detect actual provider
                'subscriber_id' => 'SIMBA_' . time() . '_' . rand(1000, 9999),
                'is_subscriber' => true
            ];
        }
        
        return null;
    }

    // NEW: Check math enrollment
    private function checkMathEnrollment($user)
    {
        return DB::table('house_role_user')
            ->where('user_id', $user->id)
            ->where('house_id', 1)    // K-6 Singapore Math
            ->where('role_id', 6)     // Student role
            ->where('payment_status', '!=', 'cancelled')
            ->first();
    }

    // NEW: Create math enrollment
    private function createMathEnrollment($user)
    {
        return DB::table('house_role_user')->insertGetId([
            'house_id' => 1,        // K-6 Singapore Math
            'role_id' => 6,         // Student
            'user_id' => $user->id,
            'progress' => 0,
            'transaction_id' => 'TELCO_' . strtoupper($user->telco_provider) . '_' . time(),
            'payment_email' => 'billing@allgifted.com',
            'purchaser_id' => $user->id,
            'start_date' => now()->toDateString(),
            'expiry_date' => now()->addMonth()->toDateString(),
            'places_alloted' => 1,
            'amount_paid' => 3.00,
            'currency_code' => 'SGD',
            'payment_status' => 'ACTIVE_TELCO',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    // NEW: Activate telco service
    private function activateTelcoService($user)
    {
        // TODO: Call actual telco API to activate billing
        \Log::info("Activating telco service for user {$user->id} on {$user->telco_provider}");
        return true;
    }

    // NEW: Send SMS
    private function sendSMS($phone, $message)
    {
        // TODO: Implement SMS sending
        \Log::info("SMS to {$phone}: {$message}");
        return true;
    }

    private function sendEmailVerification($user)
    {
        $otp = rand(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();
        
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', ['error' => $e->getMessage()]);
        }
    }
}