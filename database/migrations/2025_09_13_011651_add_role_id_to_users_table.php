<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->nullable()->after('is_admin');
            $table->foreign('role_id')->references('id')->on('roles');
        });
        
        // Data migration: Set default roles based on existing data
        $this->migrateExistingData();
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
    
    private function migrateExistingData()
    {
        // Create basic roles if they don't exist
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'role' => 'system_admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'role' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'role' => 'qa_reviewer', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Migrate existing users
        DB::table('users')->where('is_admin', 1)->update(['role_id' => 1]); // Admin
        DB::table('users')->where('is_admin', 0)->whereNull('role_id')->update(['role_id' => 2]); // Student
    }
};