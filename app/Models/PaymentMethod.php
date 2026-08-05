<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Forma de pago que se le ofrece al cliente. El administrador las edita desde
 * el panel: cuentas, QR e instrucciones incluidas.
 */
class PaymentMethod extends Model
{
    protected $fillable = [
        'code', 'name', 'kind', 'instructions', 'account_holder', 'account_number',
        'cci', 'bank', 'phone', 'url', 'qr_path', 'requires_receipt', 'is_active', 'sort',
    ];

    protected $casts = [
        'requires_receipt' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Código reservado del enlace de pago. No es un registro editable como los
     * demás: el enlace vive en cada servicio, así que se resuelve aparte.
     */
    public const LINK_CODE = 'link';

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    /**
     * Datos de la cuenta listos para mostrar, sin las filas vacías.
     *
     * @return array<string, string>
     */
    public function accountDetails(): array
    {
        return array_filter([
            'Banco' => $this->bank,
            'Titular' => $this->account_holder,
            'Número de cuenta' => $this->account_number,
            'CCI' => $this->cci,
            'Número' => $this->phone,
        ]);
    }

    /**
     * Opciones para los selectores del panel. Incluye los códigos históricos
     * que ya están guardados en pagos antiguos, para que esos registros no se
     * queden con un método que el desplegable no sabe nombrar.
     *
     * @return array<string, string>
     */
    public static function methodOptions(): array
    {
        return static::query()->orderBy('sort')->orderBy('name')->pluck('name', 'code')->all()
            + ['efectivo' => 'Efectivo', 'manual' => 'Pago manual', 'otro' => 'Otro'];
    }

    public function icon(): string
    {
        return match ($this->code) {
            'yape', 'plin' => '<rect x="5" y="2" width="14" height="20" rx="2.5"/><path d="M11 18h2"/>',
            'transferencia' => '<path d="M3 21h18"/><path d="M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/>',
            'paypal', 'culqui', 'izipay' => '<rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/><path d="M6 15h4"/>',
            default => '<circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.5h5M9.5 14.5h5"/>',
        };
    }
}
