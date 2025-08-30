<?php

namespace App\Services;

use App\Models\User;
use App\Models\Partner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartnerService
{
    /**
     * Identify partner by phone number using database
     */
    public function identifyPartner($phoneNumber)
    {
        $partners = Partner::where('is_active', true)->get();
        
        foreach ($partners as $partner) {
            foreach ($partner->phone_prefixes as $prefix) {
                if (str_starts_with($phoneNumber, $prefix)) {
                    return $partner;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check subscriber status with partner API
     */
    public function verifySubscriber($phoneNumber, Partner $partner)
    {
        if (!$partner->verification_required || !$partner->status_sync_url) {
            return ['is_active' => true]; // Auto-approve if no verification needed
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $partner->api_key,
                'Content-Type' => 'application/json'
            ])->timeout(10)->post($partner->status_sync_url, [
                'phone_number' => $phoneNumber,
                'service' => 'all_gifted_math'
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error("Partner API failed", [
                'partner' => $partner->code,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Partner verification exception", [
                'partner' => $partner->code,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Create user based on partner configuration
     */
    public function createPartnerUser($phoneNumber, Partner $partner, $subscriberData = [])
    {
        $user = User::create([
            'phone_number' => $phoneNumber,
            'partner_id' => $partner->id,
            'partner_subscriber_id' => $subscriberData['subscriber_id'] ?? null,
            'access_type' => $partner->access_type,
            'billing_method' => $partner->billing_method,
            'lives' => $partner->default_lives,
            'features' => $partner->features,
            'status' => $partner->auto_activate ? 'active' : 'pending',
            'partner_verified' => !$partner->verification_required,
            'name' => $subscriberData['name'] ?? ucfirst($partner->name) . ' Subscriber',
            'signup_channel' => 'partner_' . $partner->code
        ]);
        
        // Set trial expiry if applicable
        if ($partner->access_type === 'trial' && $partner->trial_duration_days) {
            $user->trial_expires_at = now()->addDays($partner->trial_duration_days);
            $user->save();
        }
        
        return $user;
    }
}
