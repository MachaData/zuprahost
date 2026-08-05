<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los campos originales (disk_space, bandwidth…) son texto libre para
     * mostrar el plan contratado. Estos son numéricos y en MB porque sirven
     * para calcular el porcentaje de uso que ve el cliente.
     */
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->unsignedBigInteger('disk_used_mb')->nullable()->after('disk_space');
            $table->unsignedBigInteger('disk_limit_mb')->nullable()->after('disk_used_mb');
            $table->unsignedBigInteger('bandwidth_used_mb')->nullable()->after('bandwidth');
            $table->unsignedBigInteger('bandwidth_limit_mb')->nullable()->after('bandwidth_used_mb');
            $table->unsignedInteger('websites_used')->nullable()->after('databases_limit');
            $table->unsignedInteger('websites_limit')->nullable()->after('websites_used');
            $table->string('datacenter')->nullable()->after('websites_limit');
            $table->timestamp('usage_updated_at')->nullable()->after('datacenter');
        });
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->dropColumn([
                'disk_used_mb', 'disk_limit_mb', 'bandwidth_used_mb', 'bandwidth_limit_mb',
                'websites_used', 'websites_limit', 'datacenter', 'usage_updated_at',
            ]);
        });
    }
};
