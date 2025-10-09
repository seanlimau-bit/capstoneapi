<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\OtpService;

class OTPController extends Controller
{
    /**
     * Send OTP to user's contact (email or phone)
     */
    public function sendOtp(Request $request, OtpService $otp)
    {
        $request->validate([
            'contact' => 'required|string',
            'channel' => 'nullable|string',
        ]);

        // Normalize contact & validate channel pairing
        $norm    = $otp->normalize((string) $request->contact);
        $channel = $otp->validateChannel($request->input('channel'), $norm['type']);

        // Find or create user
        $user = $otp->findOrCreateUser($norm['type'], $norm['value']);

        // Throttle, issue, dispatch
        $otp->throttle($user, $norm['type'] === 'email');
        $code = $otp->issue($user, 5);
        $otp->dispatch($user, $code, $channel);

        return response()->json([
            'channel'    => $channel,
            'email_hint' => $otp->maskedEmail($user->email),
            'phone_hint' => $otp->maskedPhone($user->phone_number),
            'user_id'    => $user->id,
            'message'    => 'OTP sent.',
        ], 200);
    }

    /**
     * Verify OTP and authenticate user
     */
    public function verifyOtp(Request $request, OtpService $otp)
    {
        // Accept either "identifier" (legacy) or "contact" (new)
        $request->validate([
            'identifier' => 'nullable|string',
            'contact'    => 'nullable|string',
            'otp_code'   => 'required|string|size:6',
        ]);

        $contact = (string) ($request->input('contact') ?? $request->input('identifier'));
        if ($contact === '') {
            return response()->json([
                'message' => 'The contact/identifier field is required.',
                'errors'  => ['contact' => ['The contact/identifier field is required.']],
            ], 422);
        }

        // Verify OTP and update user
        $result = $otp->verifyAndUpdate($contact, (string) $request->otp_code);

        if (!$result['ok']) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        /** @var \App\Models\User $user */
        $user = $result['user'];

        // Check if user needs Kiasu screen:
        // - No prior diagnostic (last_test_date null AND maxile_level <= 0)
        // - AND no age anchor (dob null AND birth_year null)
        $noPriorDiagnostic = is_null($user->last_test_date) && ((float) $user->maxile_level <= 0.0);
        $noAgeAnchor       = is_null($user->date_of_birth) && is_null($user->birth_year);
        $kiasuRecommended  = ($noPriorDiagnostic && $noAgeAnchor);

        // Create token for the user (needed for subsequent API calls)
        $token = $user->createToken('login')->plainTextToken;

        // If minimal profile missing → return profile completion response
        if ($result['needs_profile']) {
            return response()->json([
                'requires_profile_completion' => true,
                'is_new_user'       => $result['is_new_user'],
                'kiasu_recommended' => $kiasuRecommended,
                'user_id'           => $user->id,
                'contact'           => $user->email ?? $user->phone_number,
                'first_name'        => $user->firstname ?? null,
                'dob'               => $user->date_of_birth ?? null,
                'birth_year'        => $user->birth_year ?? null,
                'pc_token'          => $result['pc_token'],
                'token'             => $token,  // ← NOW RETURNS A REAL TOKEN!
                'message'           => 'Tell us a birth year (or date of birth) to calibrate your starting level.',
            ], 200);
        }

        // Happy path → full login response
        $resp = $this->loginResponse($user)->getData(true);
        $resp['requires_profile_completion'] = false;
        $resp['is_new_user']       = $result['is_new_user'];
        $resp['kiasu_recommended'] = $kiasuRecommended;

        return response()->json($resp, 200);
    }

    /**
     * Generate login response with user data
     */
    private function loginResponse(User $user)
    {
        $mathEnrollment = $this->checkMathEnrollment($user);
        $hasAccess = $mathEnrollment && $mathEnrollment->expiry_date >= now()->toDateString();
        $token = $user->createToken('login')->plainTextToken;

        // Format DOB safely to YYYY-MM-DD, allow null
        $dob = $user->date_of_birth
            ? \Illuminate\Support\Carbon::parse($user->date_of_birth)->toDateString()
            : null;

        $firstName = $user->firstname ?? null;

        $response = [
            'message'         => 'OTP verified. Login successful.',
            'token'           => $token,
            'user_id'         => $user->id,
            'first_name'      => $firstName,
            'dob'             => $dob,
            'contact'         => $user->email ?? $user->phone_number,
            'is_subscriber'   => !is_null($dob) && !is_null($firstName),
            'maxile_level'    => (int) $user->maxile_level,
            'game_level'      => (int) $user->game_level,
            'lives'           => (int) $user->lives,
            'has_math_access' => $hasAccess,
            'telco_provider'  => $user->telco_provider,
            'enrollment'      => $hasAccess ? [
                'program'     => 'K-6 Singapore Math',
                'expiry_date' => $mathEnrollment->expiry_date,
                'progress'    => $mathEnrollment->progress,
            ] : null,
        ];

        return response()->json($response, 200);
    }

    /**
     * Check if user is a telco subscriber
     */
    private function checkTelcoSubscriber($contact)
    {
        return str_starts_with($contact, '+659') ? [
            'phone'         => $contact,
            'provider'      => 'simba',
            'subscriber_id' => 'SIMBA_' . time() . '_' . rand(1000, 9999),
            'is_subscriber' => true
        ] : null;
    }

    /**
     * Check user's math enrollment status
     */
    private function checkMathEnrollment($user)
    {
        return DB::table('house_role_user')
            ->where('user_id', $user->id)
            ->where('house_id', 1)
            ->where('role_id', 6)
            ->where('payment_status', '!=', 'cancelled')
            ->first();
    }

    /**
     * Create math enrollment for user
     */
    private function createMathEnrollment($user)
    {
        return DB::table('house_role_user')->insertGetId([
            'house_id'        => 1,
            'role_id'         => 6,
            'user_id'         => $user->id,
            'progress'        => 0,
            'transaction_id'  => 'TELCO_' . strtoupper($user->telco_provider) . '_' . time(),
            'payment_email'   => 'billing@allgifted.com',
            'purchaser_id'    => $user->id,
            'start_date'      => now()->toDateString(),
            'expiry_date'     => now()->addMonth()->toDateString(),
            'places_alloted'  => 1,
            'amount_paid'     => 3.00,
            'currency_code'   => 'SGD',
            'payment_status'  => 'ACTIVE_TELCO',
            'created_at'      => now(),
            'updated_at'      => now()
        ]);
    }
}