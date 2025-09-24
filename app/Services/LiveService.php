<?php
namespace App\Services;

class LiveService  // Changed class name to match filename
{
    /**
     * Get configuration for a specific partner and provider
     */
    public static function getConfig($user)
    {
        $partnerType = self::determinePartnerType($user);
        $provider = self::getProvider($user);
        
        $config = config('partners.default'); // Start with defaults
        
        // Override with partner-specific config
        if ($partnerType && $provider) {
            $partnerConfig = config("partners.{$partnerType}.{$provider}");
            if ($partnerConfig) {
                $config = array_merge_recursive($config, $partnerConfig);
            }
        } elseif ($partnerType) {
            $partnerConfig = config("partners.{$partnerType}.default");
            if ($partnerConfig) {
                $config = array_merge_recursive($config, $partnerConfig);
            }
        }
        
        return $config;
    }
    
    /**
     * Determine partner type from user
     */
    private static function determinePartnerType($user)
    {
        if ($user->telco_provider) {
            return 'telco';
        }
        
        if ($user->school_id) {
            return 'schools';
        }
        
        if ($user->tuition_center_id) {
            return 'tuition_centers';
        }
        
        if (!$user->is_subscriber) {
            return 'free';
        }
        
        return null; // Use default
    }
    
    /**
     * Get specific provider (e.g., 'simba', 'singtel')
     */
    private static function getProvider($user)
    {
        return $user->telco_provider ?? 
               $user->school_code ?? 
               $user->tuition_center_code ?? 
               'default';
    }
}