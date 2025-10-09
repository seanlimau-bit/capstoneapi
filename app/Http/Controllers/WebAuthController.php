<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Config;
use App\Models\Role;
use Twilio\Rest\Client;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;


class WebAuthController extends Controller
{
    public function showLogin()
    {
        $site = Config::first();
        return view('auth.login', ['site' => $site]);
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

    public function sendOtp(Request $request, OtpService $otpSvc)
    {
        \Log::info('=== SendOTP Started ===', $request->all());

        $request->validate([
            'identifier' => 'required|string',
            'channel'    => 'required|in:email,sms,whatsapp',
        ]);

        $identifier = (string) $request->identifier;
        $channel    = (string) $request->channel;

        $norm = $otpSvc->normalize($identifier);
        $otpSvc->validateChannel($channel, $norm['type']);

        $user = $otpSvc->findOrCreateUser($norm['type'], $norm['value']);
        $otpSvc->throttle($user,$norm['type'] === 'email');

        $otp = $otpSvc->issue($user);
        $otpSvc->dispatch($user, $otp, $channel);

        return redirect()
            ->route('auth.verify', ['identifier' => $identifier, 'channel' => $channel])
            ->with('success', 'Verification code sent successfully');
    }

    public function verifyOtp(Request $request)
    {
        // validate without auto-redirect first, so we control error placement
        $v = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'otp_code'   => 'required|string|size:6',
        ], [
            'otp_code.size' => 'Please enter the 6-digit code.',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $identifier = (string) $request->input('identifier');
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $field      = $isEmail ? 'email' : 'phone_number';

        $user = User::where($field, $identifier)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (! $user || ! $user->otp_code || ! Hash::check($request->otp_code, $user->otp_code)) {
            // bind error to the exact input name in the form: otp_code
            return back()
                ->withErrors(['otp_code' => 'Invalid or expired verification code'])
                ->withInput();
        }

        // clear OTP & mark email verified when appropriate
        $updates = ['otp_code' => null, 'otp_expires_at' => null];
        if ($isEmail && ! $user->email_verified) {
            $updates['email_verified']    = 1;
            $updates['email_verified_at'] = now();
        }
        $user->forceFill($updates)->save();

        // log in the user for web
        Auth::login($user);

        // success → redirect somewhere meaningful
        if ($user->canAccessAdmin())   return redirect()->route('admin.dashboard.index')->with('success', 'Verified!');
        if ($user->canAccessQA())      return redirect()->route('admin.qa.index')->with('success', 'Verified!');
        return redirect()->route('auth.awaiting-access')->with('success', 'Verified!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully');
    }
}