<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitud de comprobante electrónico.
     *
     * No todo lo que vendemos se factura igual: el administrador decide, por
     * servicio, si el cliente puede pedir factura desde su portal. Los datos
     * fiscales se guardan en la factura y no solo en el cliente porque son los
     * vigentes al momento de pedirla: si el cliente cambia de razón social
     * después, el comprobante ya emitido no debe cambiar con él.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('allows_invoice_request')->default(false)->after('auto_renew');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('invoice_requested_at')->nullable()->after('sunat_cdr_path');
            $table->string('billing_document_type')->nullable()->after('invoice_requested_at');
            $table->string('billing_document_number')->nullable()->after('billing_document_type');
            $table->string('billing_name')->nullable()->after('billing_document_number');
            $table->string('billing_address')->nullable()->after('billing_name');
            $table->string('billing_email')->nullable()->after('billing_address');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('allows_invoice_request');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_requested_at', 'billing_document_type', 'billing_document_number',
                'billing_name', 'billing_address', 'billing_email',
            ]);
        });
    }
};
