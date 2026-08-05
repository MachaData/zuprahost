<x-portal-shell :client="$client" active="licenses" title="Mis licencias">
    <div class="mx-auto max-w-6xl">
        {{-- Encabezado --}}
        <div>
            <p class="ys-eyebrow">Mis licencias</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">Licencias de software</h1>
            <p class="mt-1 text-muted">Claves de activación asociadas a tu cuenta.</p>
        </div>

        {{-- Listado --}}
        <section class="ys-card mt-6 overflow-hidden !p-0">
            @if ($licenses->isEmpty())
                <div class="zp-empty">
                    <span class="ys-icon mx-auto">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2"/><path d="m17 6 2 2M15 8l2 2"/>
                        </svg>
                    </span>
                    <p class="mt-4 font-semibold text-ink">No tienes licencias activas</p>
                    <p class="mt-1 text-sm text-muted">Al contratar un producto con licencia, su clave aparecerá aquí.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Producto</th>
                                <th>Dominio autorizado</th>
                                <th>Versión</th>
                                <th>Vence</th>
                                <th class="text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($licenses as $license)
                                <tr>
                                    <td><span class="zp-key">{{ $license->license_key }}</span></td>
                                    <td class="font-medium text-ink">{{ $license->product?->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $license->authorized_domain ?? '—' }}</td>
                                    <td class="text-muted">{{ $license->version ?? '—' }}</td>
                                    <td @class(['text-red-600 font-medium' => $license->expires_at?->isPast(), 'text-muted' => ! $license->expires_at?->isPast()])>
                                        {{ $license->expires_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="text-right"><x-status-badge :status="$license->status"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($licenses->hasPages())
            <div class="mt-6">{{ $licenses->links() }}</div>
        @endif
    </div>
</x-portal-shell>
