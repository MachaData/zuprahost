@props([
    'label',
    'percent' => null,
    'used' => null,
    'limit' => null,
    'unit' => null,
])

@php
    // Sin límite conocido no hay porcentaje que mostrar; la barra queda gris.
    $value = $percent === null ? null : max(0, $percent);
    $width = $value === null ? 0 : min(100, $value);

    $tone = match (true) {
        $value === null => 'bg-slate-300',
        $value >= \App\Models\Hosting::CRITICAL_USAGE => 'bg-red-500',
        $value >= 75 => 'bg-amber-500',
        default => 'bg-emerald-500',
    };

    $text = match (true) {
        $value === null => 'text-muted',
        $value >= \App\Models\Hosting::CRITICAL_USAGE => 'text-red-600',
        $value >= 75 => 'text-amber-600',
        default => 'text-ink',
    };
@endphp

<div {{ $attributes->class('rounded-2xl border border-line bg-white p-5') }}>
    <div class="flex items-baseline justify-between gap-3">
        <p class="zp-stat__label">{{ $label }}</p>
        <p class="zp-num text-sm font-bold {{ $text }}">
            {{ $value === null ? 'Sin medir' : $value.'%' }}
        </p>
    </div>

    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100"
        role="progressbar"
        aria-label="{{ $label }}"
        @if ($value !== null)
            aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
        @endif>
        <div class="h-full rounded-full transition-all {{ $tone }}" style="width: {{ $width }}%"></div>
    </div>

    <p class="zp-num mt-2 text-xs text-muted">
        @if ($used === null)
            Todavía no hemos medido este recurso.
        @else
            {{ $used }} de {{ $limit ?? 'ilimitado' }}{{ $unit ? ' '.$unit : '' }}
        @endif
    </p>
</div>
