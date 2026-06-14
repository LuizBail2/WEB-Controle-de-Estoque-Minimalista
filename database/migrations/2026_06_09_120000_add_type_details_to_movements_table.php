<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->string('destination', 200)->nullable();   //transferência: loja/filial destino
            $table->string('direction', 20)->nullable();       // evolução: fornecedor | cliente
            $table->string('reason', 500)->nullable();         //devolução: motivo
            $table->date('ref_date')->nullable();              //devolução: data de entrada do produto
            $table->timestamp('reversed_at')->nullable();      //estorno: quando foi estornada
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn(['destination', 'direction', 'reason', 'ref_date', 'reversed_at']);
        });
    }
};
