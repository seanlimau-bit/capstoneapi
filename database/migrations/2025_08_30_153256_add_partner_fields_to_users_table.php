<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check and add partner_id if it doesn't exist
            if (!Schema::hasColumn('users', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('id');
            }
            
            // Check and add partner_subscriber_id if it doesn't exist
            if (!Schema::hasColumn('users', 'partner_subscriber_id')) {
                $table->string('partner_subscriber_id')->nullable()->after('partner_id');
            }
            
            // Check and add access_type if it doesn't exist
            if (!Schema::hasColumn('users', 'access_type')) {
                $table->enum('access_type', ['premium', 'freemium', 'trial', 'basic', 'free'])
                      ->default('free')->after('status');
            }
            
            // Check and add billing_method if it doesn't exist
            if (!Schema::hasColumn('users', 'billing_method')) {
                $table->string('billing_method')->nullable()->after('access_type');
            }
            
            // Check and add partner_features if it doesn't exist
            if (!Schema::hasColumn('users', 'partner_features')) {
                $table->json('partner_features')->nullable()->after('billing_method');
            }
            
            // Check and add partner_verified if it doesn't exist
            if (!Schema::hasColumn('users', 'partner_verified')) {
                $table->boolean('partner_verified')->default(false)->after('partner_features');
            }
            
            // Check and add partner_status_updated_at if it doesn't exist
            if (!Schema::hasColumn('users', 'partner_status_updated_at')) {
                $table->timestamp('partner_status_updated_at')->nullable()->after('partner_verified');
            }
            
            // Check and add trial_expires_at if it doesn't exist
            if (!Schema::hasColumn('users', 'trial_expires_at')) {
                $table->timestamp('trial_expires_at')->nullable()->after('partner_status_updated_at');
            }
            
            // Check and add suspended_at if it doesn't exist
            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('trial_expires_at');
            }
            
            // Check and add cancelled_at if it doesn't exist
            if (!Schema::hasColumn('users', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('suspended_at');
            }
            
            // Check and add lives if it doesn't exist
            if (!Schema::hasColumn('users', 'lives')) {
                $table->integer('lives')->default(3)->after('game_level');
            }
            
            // Check and add signup_channel if it doesn't exist
            if (!Schema::hasColumn('users', 'signup_channel')) {
                $table->string('signup_channel')->default('direct')->after('cancelled_at');
            }
            
            // Check and add OTP fields if they don't exist
            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code')->nullable();
            }
            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
        });
        
        // Add foreign key constraint separately (only if partner_id column exists)
        if (Schema::hasColumn('users', 'partner_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if foreign key doesn't already exist
                try {
                    $table->foreign('partner_id')->references('id')->on('partners');
                } catch (Exception $e) {
                    // Foreign key might already exist, ignore the error
                }
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first
            try {
                $table->dropForeign(['partner_id']);
            } catch (Exception $e) {
                // Foreign key might not exist, ignore the error
            }
            
            // Drop columns that exist
            $columnsToCheck = [
                'partner_id', 'partner_subscriber_id', 'access_type', 
                'billing_method', 'partner_features', 'partner_verified',
                'partner_status_updated_at', 'trial_expires_at',
                'suspended_at', 'cancelled_at', 'signup_channel',
                'otp_code', 'otp_expires_at'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}