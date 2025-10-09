<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_field_progress', function (Blueprint $table) {
            $table->id(); // bigint unsigned
            
            // THIS IS THE KEY FIX - must be unsignedBigInteger to match diagnostic_sessions.id
            $table->unsignedBigInteger('session_id'); // Changed from unsignedInteger
            $table->unsignedInteger('field_id');
            
            $table->integer('current_level')->default(100)
                  ->comment('Current maxile level being tested');
            $table->integer('wrong_count_at_level')->default(0)
                  ->comment('How many times wrong at current level');
            $table->integer('final_level')->nullable()
                  ->comment('Final determined level for this field');
            $table->boolean('completed')->default(false)
                  ->comment('Whether this field assessment is complete');
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('session_id')
                  ->references('id')
                  ->on('diagnostic_sessions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_field_progress');
    }
};