<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->enum('billing_cycle', ['mensual', 'anual', 'unico'])->default('mensual');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->date('next_renewal_at')->nullable();
            $table->enum('status', ['activo', 'pendiente', 'suspendido', 'cancelado', 'vencido'])->default('pendiente');
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
