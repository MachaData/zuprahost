<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasta ahora el origen del dominio se adivinaba comparando "provider"
     * con el nombre de la empresa. Se vuelve explícito: quién lo registró y
     * si la renovación corre por nuestra cuenta, que son cosas distintas
     * (un dominio traído de fuera puede pasar a renovarse con nosotros).
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->enum('origin', ['propio', 'externo'])->default('propio')->after('provider');
            $table->boolean('renewal_managed')->default(true)->after('origin');
            $table->index('origin');
        });

        // Los dominios ya cargados con otro proveedor pasan a ser externos.
        DB::table('domains')
            ->whereNotNull('provider')
            ->where('provider', '!=', config('app.name'))
            ->update(['origin' => 'externo']);
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex(['origin']);
            $table->dropColumn(['origin', 'renewal_managed']);
        });
    }
};
