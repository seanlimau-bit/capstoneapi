<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnersTable extends Migration
{
    public function up()
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'simba', 'singtel', etc.
            $table->string('name'); // 'Simba Telecom'
            $table->enum('access_type', ['premium', 'freemium', 'trial', 'basic']);
            $table->string('billing_method'); // 'carrier_billing', 'direct_pay'
            $table->integer('default_lives')->nullable(); // null = unlimited
            $table->integer('trial_duration_days')->nullable();
            $table->json('features'); // JSON array of features
            $table->boolean('verification_required')->default(true);
            $table->boolean('auto_activate')->default(false);
            $table->string('api_key')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('status_sync_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // Additional flexible config
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('partners');
    }
}