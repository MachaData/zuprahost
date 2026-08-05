<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'user_id', 'type', 'name', 'document_type', 'document_number', 'wants_invoice',
        'email', 'phone', 'whatsapp', 'address', 'city', 'country',
        'status', 'notes', 'registered_at',
    ];

    protected $casts = [
        'wants_invoice' => 'boolean',
        'registered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            $client->registered_at ??= now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * Métodos habilitados explícitamente para este cliente.
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class);
    }

    /**
     * Los métodos que el cliente ve al pagar.
     *
     * Sin selección propia hereda todos los activos: así un método nuevo llega
     * solo a quien no tiene restricciones puestas, y quien sí las tiene
     * conserva las suyas. Los inactivos nunca aparecen, aunque estén marcados.
     *
     * @return \Illuminate\Support\Collection<int, PaymentMethod>
     */
    public function availablePaymentMethods(): \Illuminate\Support\Collection
    {
        $own = $this->paymentMethods()->where('is_active', true)->orderBy('sort')->orderBy('name')->get();

        return $own->isNotEmpty() ? $own : PaymentMethod::active()->get();
    }

    public function hasCustomPaymentMethods(): bool
    {
        return $this->paymentMethods()->exists();
    }

    /**
     * Total facturado sin contar las facturas anuladas.
     */
    public function totalInvoiced(): float
    {
        return (float) $this->invoices()->where('status', '!=', 'anulado')->sum('total');
    }

    /**
     * Total efectivamente cobrado: solo pagos validados.
     */
    public function totalCollected(): float
    {
        return (float) $this->payments()->where('status', 'aprobado')->sum('amount');
    }

    /**
     * Saldo por cobrar. Se calcula factura por factura porque un pago de más
     * en una no debe tapar la deuda de otra.
     */
    public function outstandingBalance(): float
    {
        return round(
            $this->invoices()->outstanding()->get()->sum(fn (Invoice $invoice) => $invoice->balance()),
            2,
        );
    }

    public function overdueBalance(): float
    {
        return round(
            $this->invoices()->overdue()->get()->sum(fn (Invoice $invoice) => $invoice->balance()),
            2,
        );
    }

    /**
     * Añade a la consulta las columnas del estado de cuenta (facturado,
     * cobrado, saldo y vencido) como subconsultas, para que la tabla del
     * administrador se pueda ordenar y filtrar sin cargar cada factura.
     *
     * El saldo se suma factura por factura con un CASE, igual que
     * outstandingBalance(): un pago de más en una no puede tapar la deuda de
     * otra. El CASE es SQL estándar, así funciona igual en MySQL y SQLite.
     */
    public function scopeWithAccountTotals(Builder $query): Builder
    {
        $balance = fn (string $extra = '') => "coalesce((
            select sum(case when i.total > coalesce(pay.paid, 0) then i.total - coalesce(pay.paid, 0) else 0 end)
            from invoices i
            left join (
                select invoice_id, sum(amount) as paid from payments where status = 'aprobado' group by invoice_id
            ) pay on pay.invoice_id = i.id
            where i.client_id = clients.id
              and i.status in ('pendiente', 'parcial', 'vencido') {$extra}
        ), 0)";

        return $query
            ->withSum(['invoices as invoiced_sum' => fn ($q) => $q->where('status', '!=', 'anulado')], 'total')
            ->withSum(['payments as collected_sum' => fn ($q) => $q->where('status', 'aprobado')], 'amount')
            ->withMax(['payments as last_payment_at' => fn ($q) => $q->where('status', 'aprobado')], 'paid_at')
            ->selectRaw($balance().' as balance_sum')
            ->selectRaw($balance('and i.due_at < ?').' as overdue_sum', [today()]);
    }

    /**
     * Marca o desmarca al cliente como deudor según tenga facturas vencidas
     * con saldo. No toca a los suspendidos ni a los prospectos: esos estados
     * los decide el administrador a mano.
     */
    public function syncDebtorStatus(): void
    {
        $isDebtor = $this->overdueBalance() > 0;

        if ($isDebtor && $this->status === 'activo') {
            $this->update(['status' => 'deudor']);
        } elseif (! $isDebtor && $this->status === 'deudor') {
            $this->update(['status' => 'activo']);
        }
    }

    public function hasRuc(): bool
    {
        return $this->document_type === 'ruc' && ! empty($this->document_number);
    }

    /**
     * Tipo de comprobante por defecto según la elección del cliente:
     * - No desea facturación  → nota de venta (interna)
     * - Desea y tiene RUC     → factura
     * - Desea sin RUC         → boleta
     */
    public function defaultDocumentType(): string
    {
        if (! $this->wants_invoice) {
            return 'nota_venta';
        }

        return $this->hasRuc() ? 'factura' : 'boleta';
    }
}
