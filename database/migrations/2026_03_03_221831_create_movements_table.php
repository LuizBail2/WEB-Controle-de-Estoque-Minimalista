<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['entrada', 'saida', 'ajuste', 'transferencia', 'devolucao']);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

 
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
