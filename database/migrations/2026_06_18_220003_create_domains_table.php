<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('renewal_price', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->enum('status', ['activo', 'por_vencer', 'vencido', 'suspendido'])->default('activo');
            $table->text('nameservers')->nullable();
            $table->boolean('whois_protection')->default(false);
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
