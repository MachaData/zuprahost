<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan')->nullable();
            $table->string('server')->nullable();
            $table->string('server_ip')->nullable();
            $table->string('directadmin_user')->nullable();
            $table->string('disk_space')->nullable();
            $table->string('bandwidth')->nullable();
            $table->string('email_accounts')->nullable();
            $table->string('databases_limit')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', ['activo', 'suspendido', 'vencido', 'cancelado'])->default('activo');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostings');
    }
};
