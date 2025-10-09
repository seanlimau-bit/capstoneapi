<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Hash, Mail};
use Illuminate\Support\Str;
use App\Mail\SendOtpMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;

class OtpService
{
    public function normalize(string $identifier): array
    {
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        if ($isEmail) {
            return ['type' => 'email', 'value' => mb_strtolower($identifier)];
        }
        // minimal phone normalization; keep as-is to match your working flow
        return ['type' => 'phone', 'value' => $identifier];
    }

    public function validateChannel(?string $channel, string $idType): string
    {
        $channel = $channel ?: ($idType === 'email' ? 'email' : 'sms');  // <- default
        if (!in_array($channel, ['email','sms','whatsapp'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'channel' => ['Invalid channel.'],
            ]);
        }
        if ($channel === 'email' && $idType !== 'email') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'identifier' => ['Email address required for email channel.'],
            ]);
        }
        if (in_array($channel, ['sms','whatsapp'], true) && $idType !== 'phone') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'identifier' => ['Phone number required for SMS/WhatsApp.'],
            ]);
        }
        return $channel;
    }


    public function findOrCreateUser(string $idType, string $value): User
    {
        return User::firstOrCreate(
            $idType === 'email' ? ['email' => $value] : ['phone_number' => $value],
            [
                'status'         => 'active',
                'access_type'    => 'free',
                'signup_channel' => 'direct',
                'role_id'        => 6,
                'diagnostic'     => true,
                'payment_method' => 'free',
            ]
        );
    }

    public function verifyWeb(string $identifier, string $otp): array
    {
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone_number';

        $user = User::where($field, $identifier)
            ->where('otp_code', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return ['ok' => false, 'error' => 'Invalid or expired verification code'];
        }

        // mark verified (email path) + clear OTP
        $updates = [
            'otp_code'       => null,
            'otp_expires_at' => null,
            'activated_at'   => $user->activated_at ?? now(),
        ];
        if ($isEmail && !$user->email_verified) {
            $updates['email_verified']    = 1;
            $updates['email_verified_at'] = now();
        }
        $user->update($updates);

        // role/bootstrap like your controller
        $isNewUser = !$user->role_id;
        if ($isNewUser) {
            $defaultRoleId = Role::where('role', 'LIKE', '%Student%')->first()?->id ?? 6;
            $user->update([
                'role_id' => $defaultRoleId,
                'status'  => 'pending',
            ]);
        }

        Auth::login($user);

        return ['ok' => true, 'user' => $user];
    }

    // --- your existing senders (moved verbatim) ---
    private function sendSmsOtp(string $phoneNumber, string $otp): void
    {
        $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
        $twilio->messages->create($phoneNumber, [
            'from' => config('services.twilio.from'),
            'body' => "Your All Gifted verification code is: {$otp}. Valid for 10 minutes.",
        ]);
    }

    private function sendWhatsAppOtp(string $phoneNumber, string $otp): void
    {
        $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
        $twilio->messages->create("whatsapp:{$phoneNumber}", [
            'from' => 'whatsapp:' . config('services.twilio.whatsapp'),
            'body' => "Your All Gifted verification code is: {$otp}\n\nValid for 10 minutes.",
        ]);
    }

    /** Throttle per contact (email/phone). */
    public function throttle(User $user, bool $isEmail, int $seconds = 30): void
    {
        $key = 'otp:cooldown:' . ($isEmail ? 'e:' . $user->email : 'p:' . $user->phone_number);
        if (Cache::has($key)) {
            abort(response()->json(['message' => 'Please wait a moment before requesting another code.'], 429));
        }
        Cache::put($key, 1, now()->addSeconds($seconds));
    }

    /** Issue a new OTP, hash at rest, store expiry, return plaintext for transport. */
    public function issue(User $user, int $ttlMinutes = 5): string
    {
        $code = (string) random_int(100000, 999999);
        $user->forceFill([
            'otp_code'       => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $code;
    }

    /**
     * Dispatch OTP:
     * - Always email if the user has an email (your requirement)
     * - Optionally also use the preferred channel (sms/whatsapp) if provided
     */
    public function dispatch(User $user, string $code, ?string $preferred = null): void
    {
        if (!empty($user->email)) {
            // In dev: ->send(); in prod: ->queue() with a worker
            Mail::to($user->email)->send(new SendOtpMail($code));
        }

        if ($preferred === 'sms' && !empty($user->phone_number)) {
            // SmsService::send($user->phone_number, "Your login code is $code");
        } elseif ($preferred === 'whatsapp' && !empty($user->phone_number)) {
            // WhatsAppService::send($user->phone_number, "Your login code is $code");
        }
    }

    /** Maskers now live in the service. */
    public function maskedEmail(?string $email): ?string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
        [$u, $d] = explode('@', $email, 2);
        $prefix = mb_substr($u, 0, min(2, mb_strlen($u)));
        return $prefix . '***@' . $d;
    }

    public function maskedPhone(?string $phone): ?string
    {
        if (empty($phone)) return $phone;
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 6) return '***' . $digits;
        return substr($digits, 0, strlen($digits) - 4) . '****';
    }

    /**
     * Verify an OTP and update user state.
     * - Validates by Hash::check against unexpired otp_code
     * - Clears otp_code/otp_expires_at
     * - Marks email as verified when contact is email
     * - Returns a structured result for the controller to decide next step
     */
    public function verifyAndUpdate(string $contact, string $code): array
    {
        $field = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        /** @var User|null $user */
        $user = User::where($field, $contact)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user || !$user->otp_code || !Hash::check($code, $user->otp_code)) {
            return ['ok' => false, 'reason' => 'invalid_or_expired'];
        }

        // Clear OTP and mark email verified if needed
        $updates = ['otp_code' => null, 'otp_expires_at' => null];
        if ($field === 'email' && !$user->email_verified) {
            $updates['email_verified']    = 1;
            $updates['email_verified_at'] = now();
        }
        $user->forceFill($updates)->save();

        // Minimal profile check
        $hasMinProfile = !empty($user->date_of_birth) || !empty($user->birth_year);
        $isNewUser     = (empty($user->last_test_date) && (float)$user->maxile_level <= 0.0);

        if (!$hasMinProfile) {
            $pcToken = Str::random(40);
            Cache::put("pc_token:{$pcToken}", $user->id, now()->addMinutes(15));

            return [
                'ok'            => true,
                'needs_profile' => true,
                'user'          => $user,
                'pc_token'      => $pcToken,
                'is_new_user'   => $isNewUser,
            ];
        }

        return [
            'ok'            => true,
            'needs_profile' => false,
            'user'          => $user,
            'is_new_user'   => $isNewUser,
        ];
    }
}
