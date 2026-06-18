<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['natural', 'empresa', 'agencia'])->default('natural');
            $table->string('name');
            $table->enum('document_type', ['dni', 'ruc', 'ce', 'pasaporte', 'otro'])->default('dni');
            $table->string('document_number')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Perú');
            $table->enum('status', ['activo', 'suspendido', 'deudor', 'prospecto'])->default('prospecto');
            $table->text('notes')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('document_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
