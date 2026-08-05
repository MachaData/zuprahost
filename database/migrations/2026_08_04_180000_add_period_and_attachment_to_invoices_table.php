<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periodo facturado y comprobante adjunto.
     *
     * El hosting se cobra por tramos ("agosto 2026", "01/08/26 – 31/07/27") y
     * eso no se deduce de la fecha de emisión: una factura de renovación se
     * emite antes de que empiece el periodo que cubre. Guardarlo explícito
     * permite además no volver a facturar un tramo ya facturado.
     *
     * El adjunto es para el comprobante que emitimos fuera del sistema —un PDF
     * del contador, un recibo escaneado— y que el cliente debe poder descargar.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('due_at');
            $table->date('period_end')->nullable()->after('period_start');
            $table->string('attachment_path')->nullable()->after('sunat_cdr_path');
            $table->string('attachment_name')->nullable()->after('attachment_path');

            $table->index(['client_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'period_start']);
            $table->dropColumn(['period_start', 'period_end', 'attachment_path', 'attachment_name']);
        });
    }
};
