<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'billing_cycle', 'price', 'cost',
        'description', 'is_active', 'is_renewable',
        'email_accounts_limit', 'disk_limit_mb', 'bandwidth_limit_mb',
        'websites_limit', 'databases_limit', 'panel_type', 'specs',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_renewable' => 'boolean',
        'specs' => 'array',
        'email_accounts_limit' => 'integer',
        'disk_limit_mb' => 'integer',
        'bandwidth_limit_mb' => 'integer',
        'websites_limit' => 'integer',
        'databases_limit' => 'integer',
    ];

    /**
     * Lo que incluye el plan, listo para pintar: primero las cuotas con
     * número, después lo que se cargó como texto libre en "specs".
     *
     * Un límite en 0 se muestra como "Ilimitado"; en null significa que esa
     * cuota no aplica a este producto (un VPS no tiene buzones) y se omite.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function planFeatures(): array
    {
        $quotas = [
            ['Cuentas de correo', $this->email_accounts_limit, fn ($v) => $v.' '.($v === 1 ? 'cuenta' : 'cuentas')],
            ['Espacio en disco', $this->disk_limit_mb, fn ($v) => Hosting::formatMegabytes($v)],
            ['Transferencia', $this->bandwidth_limit_mb, fn ($v) => Hosting::formatMegabytes($v).'/mes'],
            ['Sitios web', $this->websites_limit, fn ($v) => $v.' '.($v === 1 ? 'sitio' : 'sitios')],
            ['Bases de datos', $this->databases_limit, fn ($v) => (string) $v],
        ];

        $features = [];

        foreach ($quotas as [$label, $value, $format]) {
            if ($value === null) {
                continue;
            }

            $features[] = ['label' => $label, 'value' => $value === 0 ? 'Ilimitado' : $format($value)];
        }

        foreach ($this->specs ?? [] as $spec) {
            if (! empty($spec['label'])) {
                $features[] = ['label' => $spec['label'], 'value' => $spec['value'] ?? '✓'];
            }
        }

        return $features;
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
