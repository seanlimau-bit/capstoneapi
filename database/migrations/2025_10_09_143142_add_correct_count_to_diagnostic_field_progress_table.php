<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_field_progress', function (Blueprint $table) {
            $table->integer('correct_count_at_level')
                  ->default(0)
                  ->after('wrong_count_at_level')
                  ->comment('Total correct answers for this field');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_field_progress', function (Blueprint $table) {
            $table->dropColumn('correct_count_at_level');
        });
    }
};