<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // cria um índice simples em user_id para a foreign key poder se apoiar nele.
        Schema::table('products', function (Blueprint $table) {
            $table->index('user_id', 'products_user_id_index');
        });

        // agora o índice único composto pode ser removido
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_user_id_code_unique');
        });
    }

    public function down(): void
    {
        //recria o índice único
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_user_id_index');
        });
    }
};
