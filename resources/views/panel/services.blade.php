<x-portal-shell :client="$client" active="services" title="Mis servicios">
    <div class="mx-auto max-w-6xl">
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="ys-eyebrow">Mis servicios</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">Servicios contratados</h1>
                <p class="mt-1 text-muted">
                    {{ $activeCount }} {{ $activeCount === 1 ? 'servicio activo' : 'servicios activos' }} en tu cuenta.
                </p>
            </div>
            <a href="{{ url('/contratar') }}" class="ys-btn ys-btn--primary">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Contratar servicio
            </a>
        </div>

        {{-- Listado --}}
        <section class="ys-card mt-6 overflow-hidden !p-0">
            @if ($services->isEmpty())
                <div class="zp-empty">
                    <span class="ys-icon mx-auto">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="8" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/>
                        </svg>
                    </span>
                    <p class="mt-4 font-semibold text-ink">Todavía no tienes servicios contratados</p>
                    <p class="mt-1 text-sm text-muted">Cuando contrates hosting, correo o mantenimiento, aparecerán aquí.</p>
                    <a href="{{ url('/contratar') }}" class="ys-link mt-4 justify-center">Contratar un servicio →</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Ciclo</th>
                                <th class="text-right">Precio</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $service)
                                <tr>
                                    <td>
                                        <a href="{{ route('panel.service', $service) }}" class="font-medium text-ink hover:text-brand-700">
                                            {{ $service->name }}
                                        </a>
                                        @if ($service->domain?->name)
                                            <p class="mt-0.5 text-xs text-muted">{{ $service->domain->name }}</p>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ ucfirst($service->billing_cycle ?? '—') }}</td>
                                    <td class="zp-num text-right font-medium">S/ {{ number_format((float) $service->price, 2) }}</td>
                                    <td @class(['text-red-600 font-medium' => $service->ends_at?->isPast(), 'text-muted' => ! $service->ends_at?->isPast()])>
                                        {{ $service->ends_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td><x-status-badge :status="$service->status"/></td>
                                    <td class="text-right">
                                        <a href="{{ route('panel.service', $service) }}"
                                            class="ys-btn ys-btn--ghost border border-line !px-3 !py-2 !text-xs">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($services->hasPages())
            <div class="mt-6">{{ $services->links() }}</div>
        @endif
    </div>
</x-portal-shell>
