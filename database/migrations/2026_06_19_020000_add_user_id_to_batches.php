<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Cria a coluna como nullable primeiro, para poder preencher os
        //    registros existentes antes de torná-la obrigatória.
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')
                      ->constrained('users')->cascadeOnDelete();
            }
        });

        // 2) Preenche o dono de cada lote a partir do produto a que ele pertence.
        //    (O lote sempre tem product_id, e o produto tem user_id = dono.)
        DB::statement('
            UPDATE batches
            JOIN products ON products.id = batches.product_id
            SET batches.user_id = products.user_id
            WHERE batches.user_id IS NULL
        ');

        // 3) Remove eventuais lotes órfãos (sem produto válido) que ficariam sem dono.
        DB::table('batches')->whereNull('user_id')->delete();
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
