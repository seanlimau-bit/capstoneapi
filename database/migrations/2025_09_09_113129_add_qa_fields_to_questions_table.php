<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQaFieldsToQuestionsTable extends Migration
{
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('qa_status', ['unreviewed', 'approved', 'flagged', 'needs_revision'])
                  ->default('unreviewed')
                  ->after('updated_at');
            
            // Use unsignedInteger to match users.id (int unsigned)
            $table->unsignedInteger('qa_reviewer_id')->nullable()->after('qa_status');
            
            $table->text('qa_notes')->nullable()->after('qa_reviewer_id');
            $table->timestamp('qa_reviewed_at')->nullable()->after('qa_notes');
            
            // Add foreign key constraint
            $table->foreign('qa_reviewer_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['qa_reviewer_id']);
            $table->dropColumn(['qa_status', 'qa_reviewer_id', 'qa_notes', 'qa_reviewed_at']);
        });
    }
}