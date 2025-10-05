<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Questions: flag sentinels
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'is_diagnostic')) {
                $table->boolean('is_diagnostic')->default(false)->after('difficulty_id');
            }
            $table->index('is_diagnostic');
        });

        // 2) Sessions (user_id must match users.id which is INT UNSIGNED in your DB)
        Schema::create('diagnostic_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');                          // BIGINT UNSIGNED
            $table->unsignedInteger('user_id')->nullable();        // INT UNSIGNED (matches users.id)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('status', ['in_progress','completed','abandoned'])->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 3) Responses (session_id -> BIGINT; question/skill/track -> INT to match your existing tables)
        Schema::create('diagnostic_responses', function (Blueprint $table) {
            $table->bigIncrements('id');                              // BIGINT
            $table->unsignedBigInteger('diagnostic_session_id');      // FK -> diagnostic_sessions.id
            $table->foreign('diagnostic_session_id')
                  ->references('id')->on('diagnostic_sessions')->onDelete('cascade');

            $table->unsignedInteger('question_id');                   // matches questions.id (INT UNSIGNED)
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');

            $table->unsignedInteger('skill_id');                      // matches skills.id (assumed INT UNSIGNED)
            $table->foreign('skill_id')->references('id')->on('skills');

            $table->unsignedInteger('track_id');                      // matches tracks.id (assumed INT UNSIGNED)
            $table->foreign('track_id')->references('id')->on('tracks');

            $table->boolean('is_correct')->nullable();
            $table->integer('response_ms')->nullable();
            $table->text('answer_given')->nullable();
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            $table->index(['diagnostic_session_id']);
            $table->index(['question_id']);
        });

        // 4) field_user: link a placement row to the diagnostic session that produced it
        Schema::table('field_user', function (Blueprint $table) {
            if (!Schema::hasColumn('field_user', 'source_session_id')) {
                $table->unsignedBigInteger('source_session_id')->nullable()->after('month_achieved');
                $table->foreign('source_session_id')
                      ->references('id')->on('diagnostic_sessions')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        // Drop FK first to avoid constraint errors
        if (Schema::hasColumn('field_user', 'source_session_id')) {
            Schema::table('field_user', function (Blueprint $table) {
                $table->dropForeign(['source_session_id']);
                $table->dropColumn('source_session_id');
            });
        }

        Schema::dropIfExists('diagnostic_responses');
        Schema::dropIfExists('diagnostic_sessions');

        if (Schema::hasColumn('questions', 'is_diagnostic')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropIndex(['is_diagnostic']);
                $table->dropColumn('is_diagnostic');
            });
        }
    }
};
