<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('question_id')->unsigned()->default(1);
            $table->foreign('question_id')->references('id')->on('questions');

            $table->enum('report_type', [
                'wrong_answer',
                'typo',
                'unclear_question',

                'image_issue',
                'other'
            ]);
            $table->text('comment')->nullable();
            $table->enum('status', [
                'pending',
                'under_review',
                'resolved',
                'dismissed'
            ])->default('pending');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_reports');
    }
};