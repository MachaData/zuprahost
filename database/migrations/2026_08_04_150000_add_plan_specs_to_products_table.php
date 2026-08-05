<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que incluye cada plan. Las cuotas son numéricas porque distinguen un
     * plan de otro (1 cuenta de correo frente a 5) y porque al dar de alta un
     * hosting se copian como límite del cliente. "specs" queda para lo que no
     * es un número: "Backups diarios", "SSL incluido"… y sirve igual para VPS,
     * correo, mantenimiento o licencias, que no tienen disco ni buzones.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('email_accounts_limit')->nullable()->after('description');
            $table->unsignedBigInteger('disk_limit_mb')->nullable()->after('email_accounts_limit');
            $table->unsignedBigInteger('bandwidth_limit_mb')->nullable()->after('disk_limit_mb');
            $table->unsignedInteger('websites_limit')->nullable()->after('bandwidth_limit_mb');
            $table->unsignedInteger('databases_limit')->nullable()->after('websites_limit');
            $table->string('panel_type')->nullable()->after('databases_limit');
            $table->json('specs')->nullable()->after('panel_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'email_accounts_limit', 'disk_limit_mb', 'bandwidth_limit_mb',
                'websites_limit', 'databases_limit', 'panel_type', 'specs',
            ]);
        });
    }
};
