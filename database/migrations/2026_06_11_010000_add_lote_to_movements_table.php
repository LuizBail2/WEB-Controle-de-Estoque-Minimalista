<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            //Rótulo lote envolvido na movimentação
            $table->string('lote', 120)->nullable()->after('unit_price');
            //Lote criado por esta movimentação 
            $table->unsignedBigInteger('batch_id')->nullable()->after('lote');
            //Mapa de baixa por lote nas saídas FEFO
            $table->json('batch_consumption')->nullable()->after('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn(['lote', 'batch_id', 'batch_consumption']);
        });
    }
};
