<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', [
                'hosting', 'dominio', 'vps', 'correo', 'ssl', 'mantenimiento',
                'seo', 'optimizacion', 'licencia', 'desarrollo', 'soporte',
                'migracion', 'backup', 'landing', 'anuncios', 'consultoria', 'otro',
            ])->default('hosting');
            $table->enum('billing_cycle', ['mensual', 'anual', 'unico'])->default('mensual');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_renewable')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
