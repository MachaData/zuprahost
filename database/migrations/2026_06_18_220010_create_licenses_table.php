<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_key')->unique();
            $table->string('authorized_domain')->nullable();
            $table->date('activated_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', ['activa', 'vencida', 'suspendida', 'cancelada'])->default('activa');
            $table->unsignedInteger('max_activations')->default(1);
            $table->unsignedInteger('used_activations')->default(0);
            $table->string('version')->nullable();
            $table->string('download_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
