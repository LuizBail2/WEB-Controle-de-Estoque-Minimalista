<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        //Tabela de empresas
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            //CNPJ guardado CRIPTOGRAFADO
            $table->text('cnpj')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('plan', 30)->default('free');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index('owner_user_id');
        });

        //conexão usuário com a empresa
        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id');
            });
        }

        //cria uma empresa para cada dono existente e vincula dono + funcionários
        $owners = DB::table('users')->whereNull('owner_id')->orderBy('id')->get();
        foreach ($owners as $owner) {
            $companyId = DB::table('companies')->insertGetId([
                'name'          => $owner->name ? ($owner->name . ' — Empresa') : 'Minha Empresa',
                'cnpj'          => null,
                'owner_user_id' => $owner->id,
                'plan'          => 'free',
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('users')->where('id', $owner->id)->update(['company_id' => $companyId]);
            //os funcionários
            DB::table('users')->where('owner_id', $owner->id)->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['company_id']);
                $table->dropColumn('company_id');
            });
        }
        Schema::dropIfExists('companies');
    }
};
