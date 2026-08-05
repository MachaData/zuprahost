<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buzones y bases de datos consumidos frente al límite del plan, para que
     * "5 cuentas de correo" se vea como medidor y no como texto suelto.
     * Mismo criterio que disco y transferencia: el campo de texto describe el
     * plan, estos miden el consumo real.
     */
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->unsignedInteger('email_accounts_used')->nullable()->after('email_accounts');
            $table->unsignedInteger('email_accounts_limit')->nullable()->after('email_accounts_used');
            $table->unsignedInteger('databases_used')->nullable()->after('databases_limit');
            $table->unsignedInteger('databases_limit_count')->nullable()->after('databases_used');
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->dropColumn([
                'email_accounts_used', 'email_accounts_limit',
                'databases_used', 'databases_limit_count',
            ]);
        });
    }
};
