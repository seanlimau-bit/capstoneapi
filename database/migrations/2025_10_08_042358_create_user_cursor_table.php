<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ------------------------------
        // diagnostic_sessions: add metadata + resume columns (safe if missing)
        // ------------------------------
        if (Schema::hasTable('diagnostic_sessions')) {
            Schema::table('diagnostic_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('diagnostic_sessions', 'subject')) {
                    $table->string('subject', 32)->default('math')->after('user_id');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'mode')) {
                    $table->enum('mode', ['diagnostic','learning'])->default('diagnostic')->after('subject');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'seed_hint_type')) {
                    $table->enum('seed_hint_type', ['birthdate','age','grade','none'])->default('none')->after('mode');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'seed_hint_value')) {
                    $table->string('seed_hint_value', 64)->nullable()->after('seed_hint_type');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'start_maxile')) {
                    $table->decimal('start_maxile', 8, 2)->nullable()->after('seed_hint_value');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'end_maxile')) {
                    $table->decimal('end_maxile', 8, 2)->nullable()->after('start_maxile');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'item_count')) {
                    $table->integer('item_count')->default(0)->after('end_maxile');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'current_item_id')) {
                    $table->unsignedInteger('current_item_id')->nullable()->after('item_count');
                }
                if (!Schema::hasColumn('diagnostic_sessions', 'aborted_reason')) {
                    $table->string('aborted_reason', 64)->nullable()->after('current_item_id');
                }
            });

            // Add indexes only if missing
            $this->addIndexIfMissing('diagnostic_sessions', 'idx_diag_sessions_user_status', ['user_id','status']);
            $this->addIndexIfMissing('diagnostic_sessions', 'idx_diag_sessions_subject', ['subject']);
        }

        // ------------------------------
        // diagnostic_responses: add analytics columns (safe if missing)
        // ------------------------------
        if (Schema::hasTable('diagnostic_responses')) {
            Schema::table('diagnostic_responses', function (Blueprint $table) {
                if (!Schema::hasColumn('diagnostic_responses', 'presented_difficulty')) {
                    $table->decimal('presented_difficulty', 6, 3)->nullable()->after('track_id');
                }
                if (!Schema::hasColumn('diagnostic_responses', 'maxile_before')) {
                    $table->decimal('maxile_before', 8, 2)->nullable()->after('presented_difficulty');
                }
                if (!Schema::hasColumn('diagnostic_responses', 'maxile_after')) {
                    $table->decimal('maxile_after', 8, 2)->nullable()->after('maxile_before');
                }
            });

            $this->addIndexIfMissing('diagnostic_responses', 'idx_diag_resp_session', ['diagnostic_session_id']);
            $this->addIndexIfMissing('diagnostic_responses', 'idx_diag_resp_question', ['question_id']);
        }

        // ------------------------------
        // user_path_cursors: tiny universal resume bookmark
        // ------------------------------
        if (!Schema::hasTable('user_path_cursors')) {
            Schema::create('user_path_cursors', function (Blueprint $table) {
                $table->unsignedInteger('user_id');
                $table->string('subject', 32); // e.g., 'math'
                $table->enum('mode', ['diagnostic','learning','freeplay'])->default('learning');

                $table->enum('next_item_type', ['assessment','skill','field','level'])->nullable();
                $table->unsignedInteger('next_item_id')->nullable();

                $table->json('queue')->nullable();
                $table->unsignedBigInteger('diagnostic_session_id')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->primary(['user_id','subject']);
                $table->index(['diagnostic_session_id'], 'idx_upc_diag_session');

                // Optional FKs (uncomment if types match and you want strict integrity):
                // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                // $table->foreign('diagnostic_session_id')->references('id')->on('diagnostic_sessions')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_path_cursors')) {
            Schema::drop('user_path_cursors');
        }

        if (Schema::hasTable('diagnostic_responses')) {
            Schema::table('diagnostic_responses', function (Blueprint $table) {
                foreach (['presented_difficulty','maxile_before','maxile_after'] as $col) {
                    if (Schema::hasColumn('diagnostic_responses', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
            // indexes can remain; harmless on rollback
        }

        if (Schema::hasTable('diagnostic_sessions')) {
            Schema::table('diagnostic_sessions', function (Blueprint $table) {
                foreach ([
                    'subject','mode','seed_hint_type','seed_hint_value',
                    'start_maxile','end_maxile','item_count','current_item_id','aborted_reason'
                ] as $col) {
                    if (Schema::hasColumn('diagnostic_sessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
            // indexes can remain; harmless on rollback
        }
    }

    /**
     * Safely add a named index to a table if it doesn't exist (MySQL).
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }
        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    /**
     * Check if an index name exists for a given table in the current MySQL schema.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            "SELECT COUNT(1) AS c
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1",
            [$db, $table, $indexName]
        );
        return isset($row->c) && (int)$row->c > 0;
    }
};
