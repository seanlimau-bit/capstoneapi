<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // All Gifted School - Internal partner for school users
        Partner::create([
            'code' => 'allgifted_school',
            'name' => 'All Gifted School',
            'access_type' => 'premium',
            'billing_method' => 'direct_billing',
            'default_lives' => null, // Unlimited lives
            'trial_duration_days' => null,
            'features' => [
                'unlimited_practice',
                'premium_content', 
                'progress_tracking',
                'teacher_dashboard',
                'student_reports',
                'no_ads',
                'classroom_management'
            ],
            'verification_required' => true, // Verify school credentials
            'auto_activate' => false, // Manual activation after verification
            'api_key' => null, // Internal partner, no external API
            'webhook_secret' => null,
            'status_sync_url' => null,
            'is_active' => true,
            'config' => [
                'max_students_per_class' => 50,
                'teacher_features_enabled' => true,
                'bulk_student_import' => true,
                'detailed_analytics' => true
            ]
        ]);

        // SIMBA Telco - Telecom partner
        Partner::create([
            'code' => 'simba',
            'name' => 'SIMBA Telecom',
            'access_type' => 'premium',
            'billing_method' => 'carrier_billing',
            'default_lives' => null, // Unlimited lives
            'trial_duration_days' => null,
            'features' => [
                'unlimited_practice',
                'premium_content',
                'progress_tracking', 
                'no_ads',
                'priority_support',
                'offline_content'
            ],
            'verification_required' => false, // Auto-verify SIMBA subscribers
            'auto_activate' => true, // Immediate activation
            'api_key' => env('SIMBA_API_KEY'), // For subscriber verification
            'webhook_secret' => env('SIMBA_WEBHOOK_SECRET'), // For status updates
            'status_sync_url' => env('SIMBA_STATUS_URL', 'https://api.simba.sg/subscribers/verify'),
            'is_active' => true,
            'config' => [
                'carrier_billing_enabled' => true,
                'auto_renewal' => true,
                'family_plan_support' => true,
                'data_allowance_tracking' => true
            ]
        ]);
    }
}