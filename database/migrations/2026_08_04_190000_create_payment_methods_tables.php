<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Métodos de pago configurables.
     *
     * Antes eran un enum en la tabla de pagos: añadir Izipay o Culqui exigía
     * una migración. Ahora son registros que el administrador edita —con sus
     * cuentas, su QR y sus instrucciones— y el enum pasa a texto para no
     * pelearse con eso.
     *
     * El enlace de pago es la excepción: no vive aquí sino en cada servicio,
     * porque es propio de lo contratado.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            // manual: el cliente transfiere y sube comprobante.
            // enlace: se paga en una pasarela externa, sin comprobante.
            $table->enum('kind', ['manual', 'enlace'])->default('manual');
            $table->text('instructions')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('cci')->nullable();
            $table->string('bank')->nullable();
            $table->string('phone')->nullable();
            $table->string('url')->nullable();
            $table->string('qr_path')->nullable();
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort']);
        });

        // Habilitación por cliente. Sin filas, el cliente ve todos los activos:
        // así un método nuevo llega solo a quien no tiene restricciones puestas.
        Schema::create('client_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'payment_method_id']);
        });

        // Enlace de pago propio del servicio (Izipay, Culqui, PayPal…).
        Schema::table('services', function (Blueprint $table) {
            $table->string('payment_link')->nullable()->after('allows_invoice_request');
        });

        // El enum bloqueaba métodos nuevos; el catálogo ya valida los válidos.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method', 50)->default('transferencia')->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('payment_link');
        });

        Schema::dropIfExists('client_payment_method');
        Schema::dropIfExists('payment_methods');
    }
};
