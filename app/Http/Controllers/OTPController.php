<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\SendOtpMail;
use App\Models\User;

class OTPController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate(['contact' => 'required|string']);
        
        $contact = $request->contact;
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);
        
        // Find or create user
        $user = User::where($isEmail ? 'email' : 'phone_number', $contact)->first()
        ?? $this->createTelcoUser($contact);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        // Handle potential telco users
        if ($user->status === 'potential') {
            return $this->handleTelcoOtp($user);
        }
        
        // Check registration completion
        if (!$user->email_verified_at || !$user->date_of_birth) {
            return response()->json([
                'message' => 'Please complete registration first',
                'requires_registration' => true,
                'user_id' => $user->id
            ], 422);
        }
        
        return $this->sendOtpToEmail($user);
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

        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        return $user->status === 'potential' 
        ? $this->activateTelcoUser($user)
        : $this->loginResponse($user);
    }

    public function completeRegistration(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'date_of_birth' => 'required|date'
        ]);
        
        $user = User::create(array_merge($validated, [
            'name' => $validated['firstname'] . ' ' . $validated['lastname'],
            'email_verified_at' => null,
            'status' => 'active',
            'lives' => 5,
            'is_admin' => false
        ]));
        
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
        
        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null
        ]);
        
        return response()->json(['message' => 'Email verified successfully']);
    }

    // Private helper methods
    private function createTelcoUser($contact)
    {
        $telcoData = $this->checkTelcoSubscriber($contact);
        
        return $telcoData ? User::create([
            'phone_number' => $telcoData['phone'],
            'telco_provider' => $telcoData['provider'],
            'telco_subscriber_id' => $telcoData['subscriber_id'],
            'status' => 'potential',
            'email_verified_at' => null,
            'name' => 'Telco Subscriber',
            'signup_channel' => 'telco_otp_login'
        ]) : null;
    }

    private function handleTelcoOtp($user)
    {
        $otp = $this->generateAndSaveOtp($user);
        $this->sendSMS($user->phone_number, "Your All Gifted verification code: {$otp}");
        
        return response()->json([
            'message' => "Activating All Gifted Math Premium on your " . ucfirst($user->telco_provider ?? 'telco') . " bill. Enter the verification code sent to your phone.",
            'is_telco_activation' => true,
            'telco_provider' => $user->telco_provider,
            'phone' => $user->phone_number
        ]);
    }

    private function sendOtpToEmail($user)
    {
        $otp = $this->generateAndSaveOtp($user);
        
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

    private function generateAndSaveOtp($user)
    {
        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);
        return $otp;
    }

    private function activateTelcoUser($user)
    {
        try {
            $this->activateTelcoService($user);
            
            $user->update([
                'status' => 'active',
                'payment_method' => 'telco_billing',
                'activated_at' => now(),
                'email_verified_at' => now(),
                'lives' => 5
            ]);
            
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
            \Log::error('Telco activation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Activation failed. Please try again.'], 500);
        }
    }

    private function loginResponse($user)
    {
        $mathEnrollment = $this->checkMathEnrollment($user);
        $hasAccess = $mathEnrollment && $mathEnrollment->expiry_date >= now()->toDateString();
        $token = $user->createToken('login')->plainTextToken;
        $isSubscriber = !is_null($user->date_of_birth) && !is_null($user->firstname);
        
        $response = [
            'message' => 'OTP verified. Login successful.',
            'token' => $token,
            'user_id' => $user->id,
            'first_name' => $user->firstname,
            'is_subscriber' => $isSubscriber,
            'dob' => $user->date_of_birth,
            'maxile_level' => (int) $user->maxile_level,
            'game_level' => (int) $user->game_level,
            'lives' => (int) $user->lives,
            'has_math_access' => $hasAccess,
            'telco_provider' => $user->telco_provider,
            'enrollment' => $hasAccess ? [
                'program' => 'K-6 Singapore Math',
                'expiry_date' => $mathEnrollment->expiry_date,
                'progress' => $mathEnrollment->progress
            ] : null
        ];
        
    // Add global admin data if user has admin privileges
        if ($this->isAdminUser($user)) {
            $response['globalAdminData'] = $this->getGlobalAdminData();
        }
        
        return response()->json($response);
    }

    private function checkTelcoSubscriber($contact)
    {
        return str_starts_with($contact, '+659') ? [
            'phone' => $contact,
            'provider' => 'simba',
            'subscriber_id' => 'SIMBA_' . time() . '_' . rand(1000, 9999),
            'is_subscriber' => true
        ] : null;
    }

    private function checkMathEnrollment($user)
    {
        return DB::table('house_role_user')
        ->where('user_id', $user->id)
        ->where('house_id', 1)
        ->where('role_id', 6)
        ->where('payment_status', '!=', 'cancelled')
        ->first();
    }

    private function createMathEnrollment($user)
    {
        return DB::table('house_role_user')->insertGetId([
            'house_id' => 1,
            'role_id' => 6,
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

    private function activateTelcoService($user)
    {
        \Log::info("Activating telco service for user {$user->id} on {$user->telco_provider}");
        return true;
    }

    private function sendSMS($phone, $message)
    {
        \Log::info("SMS to {$phone}: {$message}");
        return true;
    }

    private function sendEmailVerification($user)
    {
        $otp = $this->generateAndSaveOtp($user, 15);
        
        try {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', ['error' => $e->getMessage()]);
        }
    }
}