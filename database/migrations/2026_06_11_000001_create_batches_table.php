<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lote', 80)->nullable();     //código do lote
            $table->date('expiry_date');                 //validade
            $table->integer('quantity')->default(0);     //quantidade neste lote
            $table->date('entry_date')->nullable();      //data de entrada do lote
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('expiry_date');
            $table->index(['product_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
