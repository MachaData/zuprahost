<x-filament-panels::page>
    @php($totals = $this->getTotals())

    <div class="grid gap-4 sm:grid-cols-3">
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Facturado</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">S/ {{ number_format($totals['invoiced'], 2) }}</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Cobrado</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-success-600">
                S/ {{ number_format($totals['collected'], 2) }}
            </p>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Solo comprobantes validados</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Por cobrar</p>
            <p @class([
                'mt-1 text-2xl font-bold tracking-tight',
                'text-warning-600' => $totals['balance'] > 0,
            ])>S/ {{ number_format($totals['balance'], 2) }}</p>
            @if ($totals['overdue'] > 0)
                <p class="mt-0.5 text-xs font-medium text-danger-600">
                    S/ {{ number_format($totals['overdue'], 2) }} ya vencido
                </p>
            @endif
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
