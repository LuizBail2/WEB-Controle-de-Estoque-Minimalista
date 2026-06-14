<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //Remove o unique global do 'code' 
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_code_unique');
        });

        //Adiciona o dono do produto
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        //Atribui os produtos ja existentes ao primeiro usuario 
        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('products')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }

        //'code' agora e unico por usuario
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('code');
        });
    }
};