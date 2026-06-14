<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'description')) {
                $table->string('description', 500)->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 16)->nullable()->after('description');
            }
            if (!Schema::hasColumn('categories', 'color')) {
                $table->string('color', 16)->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
    }
};
