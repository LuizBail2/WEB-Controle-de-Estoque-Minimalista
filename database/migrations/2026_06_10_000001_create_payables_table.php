<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            //nem todas as despesas são pagas por um fornecedor, mas sempre vai fazer a referencia
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->boolean('paid')->default(false);
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['paid', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
