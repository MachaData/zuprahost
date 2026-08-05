@props(['status' => null])

@php
    /*
    | Colores de estado del portal. Única fuente de verdad para servicios,
    | dominios, facturas y licencias: así el mismo estado se ve igual en
    | todas las pantallas.
    */
    $colors = [
        // Servicios
        'activo' => 'bg-emerald-50 text-emerald-700',
        'pendiente' => 'bg-slate-100 text-slate-600',
        'suspendido' => 'bg-amber-50 text-amber-700',
        'vencido' => 'bg-red-50 text-red-700',
        'cancelado' => 'bg-slate-100 text-slate-500',

        // Dominios
        'por_vencer' => 'bg-amber-50 text-amber-700',

        // Facturas
        'pagado' => 'bg-emerald-50 text-emerald-700',
        'parcial' => 'bg-brand-50 text-brand-700',
        'anulado' => 'bg-slate-100 text-slate-500',

        // Licencias
        'activa' => 'bg-emerald-50 text-emerald-700',
        'vencida' => 'bg-red-50 text-red-700',
        'suspendida' => 'bg-amber-50 text-amber-700',
        'cancelada' => 'bg-slate-100 text-slate-500',
    ];

    $label = ucfirst(str_replace('_', ' ', (string) $status));
@endphp

<span {{ $attributes->class(['zp-badge', $colors[$status] ?? 'bg-slate-100 text-slate-600']) }}>
    {{ $label !== '' ? $label : '—' }}
</span>
