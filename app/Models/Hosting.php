<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hosting extends Model
{
    protected $fillable = [
        'client_id', 'domain_id', 'service_id', 'plan', 'server',
        'server_ip', 'directadmin_user', 'disk_space', 'bandwidth',
        'email_accounts', 'databases_limit', 'expires_at', 'status', 'notes',
        'disk_used_mb', 'disk_limit_mb', 'bandwidth_used_mb', 'bandwidth_limit_mb',
        'websites_used', 'websites_limit', 'datacenter', 'usage_updated_at',
        'email_accounts_used', 'email_accounts_limit',
        'databases_used', 'databases_limit_count',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'usage_updated_at' => 'datetime',
        'disk_used_mb' => 'integer',
        'disk_limit_mb' => 'integer',
        'bandwidth_used_mb' => 'integer',
        'bandwidth_limit_mb' => 'integer',
        'websites_used' => 'integer',
        'websites_limit' => 'integer',
        'email_accounts_used' => 'integer',
        'email_accounts_limit' => 'integer',
        'databases_used' => 'integer',
        'databases_limit_count' => 'integer',
    ];

    /**
     * Umbral a partir del cual el consumo se pinta en rojo y se ofrece ampliar.
     */
    public const CRITICAL_USAGE = 90;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Porcentaje consumido, o null si todavía no hay medición cargada.
     * No se recorta a 100: un plan sobrepasado debe poder decir "103%".
     */
    public function diskUsagePercent(): ?int
    {
        return $this->usagePercent($this->disk_used_mb, $this->disk_limit_mb);
    }

    public function bandwidthUsagePercent(): ?int
    {
        return $this->usagePercent($this->bandwidth_used_mb, $this->bandwidth_limit_mb);
    }

    public function websitesUsagePercent(): ?int
    {
        return $this->usagePercent($this->websites_used, $this->websites_limit);
    }

    public function emailUsagePercent(): ?int
    {
        return $this->usagePercent($this->email_accounts_used, $this->email_accounts_limit);
    }

    public function databasesUsagePercent(): ?int
    {
        return $this->usagePercent($this->databases_used, $this->databases_limit_count);
    }

    public function hasUsageMetrics(): bool
    {
        return collect([
            $this->diskUsagePercent(),
            $this->bandwidthUsagePercent(),
            $this->websitesUsagePercent(),
            $this->emailUsagePercent(),
            $this->databasesUsagePercent(),
        ])->contains(fn (?int $percent) => $percent !== null);
    }

    /**
     * ¿Algún recurso está al límite? Dispara el aviso de ampliación.
     */
    public function isNearCapacity(): bool
    {
        return collect([$this->diskUsagePercent(), $this->bandwidthUsagePercent()])
            ->filter(fn (?int $percent) => $percent !== null)
            ->contains(fn (int $percent) => $percent >= self::CRITICAL_USAGE);
    }

    /**
     * Formatea megabytes como "205.6 GB" o "512 MB".
     */
    public static function formatMegabytes(?int $megabytes): string
    {
        if ($megabytes === null) {
            return '—';
        }

        return $megabytes >= 1024
            ? number_format($megabytes / 1024, 1).' GB'
            : $megabytes.' MB';
    }

    protected function usagePercent(?int $used, ?int $limit): ?int
    {
        if ($used === null || ! $limit) {
            return null;
        }

        return (int) round($used / $limit * 100);
    }
}
