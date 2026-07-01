@php
    // Catálogo real de zupraHost. Iconos de Heroicons (outline).
    $services = [
        [
            'title' => 'Hosting',
            'lead' => 'Alojamiento web <strong class="font-semibold text-ink">rápido y seguro</strong> para tu sitio o tienda, con soporte en español.',
            'icon' => '<path d="M5 12.75A2.25 2.25 0 0 1 7.25 10.5h9.5A2.25 2.25 0 0 1 19 12.75V17a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-4.25z"/><path d="M7.5 15h.008"/><path d="M6 10.5V8a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2.5"/>',
        ],
        [
            'title' => 'Dominio',
            'lead' => 'Registra o renueva tu dominio <strong class="font-semibold text-ink">.com, .pe</strong> y más, con protección WHOIS.',
            'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"/>',
        ],
        [
            'title' => 'Correo corporativo',
            'lead' => 'Correos <strong class="font-semibold text-ink">profesionales</strong> con tu propio dominio y buzones amplios.',
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        ],
        [
            'title' => 'Certificado SSL',
            'lead' => 'Asegura tu sitio con <strong class="font-semibold text-ink">HTTPS</strong> y gana la confianza de tus visitantes.',
            'icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        ],
        [
            'title' => 'Mantenimiento web',
            'lead' => 'Cuidamos, actualizamos y respaldamos tu <strong class="font-semibold text-ink">WordPress</strong> cada mes.',
            'icon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5z"/>',
        ],
        [
            'title' => 'SEO mensual',
            'lead' => 'Posicionamiento en buscadores con <strong class="font-semibold text-ink">reportes claros</strong> mes a mes.',
            'icon' => '<path d="M3 3v18h18"/><path d="m7 15 3-4 3 2 4-6"/>',
        ],
    ];
@endphp

<x-builder-shell
    eyebrow="Contratar servicio"
    heading="Nuevo servicio"
    step="Paso 1 de 3 · Elegir servicio"
    :backUrl="url('/')"
    nextLabel="Siguiente: Plan"
    nextUrl="#"
>
    {{-- Encabezado --}}
    <div class="max-w-xl">
        <p class="ys-eyebrow">Catálogo zupraHost</p>
        <h2 class="ys-title mt-3">¿Qué deseas contratar?</h2>
        <p class="ys-lead mt-3">
            Elige el servicio que necesitas. En el siguiente paso seleccionas el plan y
            el ciclo de pago; luego confirmas y listo.
        </p>
    </div>

    {{-- Catálogo --}}
    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($services as $service)
            <a href="{{ url('/client') }}" class="ys-card ys-card--interactive group flex flex-col">
                <span class="ys-icon">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        {!! $service['icon'] !!}
                    </svg>
                </span>

                <h3 class="mt-5 text-lg font-bold text-ink">{{ $service['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-muted">{!! $service['lead'] !!}</p>

                <span class="ys-link mt-6">
                    Contratar {{ mb_strtolower($service['title']) }}
                    <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </span>
            </a>
        @endforeach
    </div>
</x-builder-shell>
