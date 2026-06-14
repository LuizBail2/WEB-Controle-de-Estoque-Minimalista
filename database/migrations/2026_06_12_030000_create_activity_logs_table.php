<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->index();   //admin que recebe a notificação
            $table->unsignedBigInteger('actor_id')->nullable(); //funcionário que fez a ação
            $table->string('action', 30);                       //criou / editou / excluiu
            $table->string('subject', 60);                      //um produto / um lote / ...
            $table->string('description', 255);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
