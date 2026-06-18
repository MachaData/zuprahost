<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('status', ['pendiente', 'pagado', 'vencido', 'anulado', 'parcial'])->default('pendiente');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();

            // Facturación electrónica (apisperu / SUNAT)
            $table->enum('document_type', ['boleta', 'factura', 'nota_venta'])->default('nota_venta');
            $table->string('series')->nullable();
            $table->string('number')->nullable();
            $table->enum('sunat_status', ['no_enviado', 'enviado', 'aceptado', 'rechazado', 'anulado'])->default('no_enviado');
            $table->json('sunat_response')->nullable();
            $table->string('sunat_pdf_path')->nullable();
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
