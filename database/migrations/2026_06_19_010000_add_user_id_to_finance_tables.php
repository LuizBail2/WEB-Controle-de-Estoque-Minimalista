<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        //tabelas limpas
        DB::table('payables')->delete();
        DB::table('receivables')->delete();

        Schema::table('payables', function (Blueprint $table) {
            if (!Schema::hasColumn('payables', 'user_id')) {
                $table->foreignId('user_id')
                      ->after('id')
                      ->constrained('users')
                      ->cascadeOnDelete();
            }
        });

        Schema::table('receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('receivables', 'user_id')) {
                $table->foreignId('user_id')
                      ->after('id')
                      ->constrained('users')
                      ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            if (Schema::hasColumn('payables', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('receivables', function (Blueprint $table) {
            if (Schema::hasColumn('receivables', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
