<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enlaces de acceso de un servicio: DirectAdmin, cPanel, webmail, consola
     * del VPS, portal de licencias… Es una tabla y no columnas fijas porque
     * cada línea de negocio (hosting, correo, VPS, licencias, mantenimiento)
     * entrega accesos distintos, y un servicio puede tener varios.
     *
     * Aquí no se guardan contraseñas: solo dónde entrar y con qué usuario.
     */
    public function up(): void
    {
        Schema::create('service_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'directadmin', 'cpanel', 'plesk', 'webmail', 'wordpress',
                'vps', 'ftp', 'base_datos', 'licencia', 'otro',
            ])->default('otro');
            $table->string('label')->nullable();
            $table->string('url')->nullable();
            $table->string('username')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_accesses');
    }
};
