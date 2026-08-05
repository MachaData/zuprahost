@php
    use App\Support\Catalog;

    $brandName = \App\Support\Branding::name();

    // Motivos de compra. Es texto de venta, no datos: vive en la vista.
    $reasons = [
        [
            'title' => 'Soporte en español, con personas',
            'body' => 'Escribes y te responde alguien que conoce tu servicio. Nada de respuestas automáticas ni horarios de otro continente.',
            'icon' => '<path d="M18 12a6 6 0 1 0-12 0"/><rect x="3" y="12" width="4" height="7" rx="1.5"/><rect x="17" y="12" width="4" height="7" rx="1.5"/><path d="M19 19a3 3 0 0 1-3 3h-2"/>',
        ],
        [
            'title' => 'Facturación peruana',
            'body' => 'Boletas y facturas electrónicas con IGV, enviadas a SUNAT. Pides tu factura desde el portal y la descargas en PDF.',
            'icon' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        ],
        [
            'title' => 'Paga como te convenga',
            'body' => 'Transferencia, Yape, Plin, izipay, PayPal o Culqi. Cada cliente ve los medios que tiene habilitados.',
            'icon' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>',
        ],
        [
            'title' => 'Certificado SSL incluido',
            'body' => 'Tu sitio sale con el candado desde el primer día, sin costo adicional ni configuraciones raras.',
            'icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        ],
        [
            'title' => 'Copias de seguridad',
            'body' => 'Respaldos periódicos de tus archivos y bases de datos. Si algo se rompe, se restaura.',
            'icon' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        ],
        [
            'title' => 'Renovaciones sin sustos',
            'body' => 'Te avisamos antes de cada vencimiento y ves todo lo que vence en tu portal. Nadie pierde un dominio con nosotros.',
            'icon' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
        ],
    ];

    $faqs = [
        [
            'q' => '¿Puedo trasladar mi sitio desde otro proveedor?',
            'a' => 'Sí. Nos encargamos de la migración completa: archivos, bases de datos y cuentas de correo. Coordinamos el cambio para que tu sitio no deje de estar disponible, y no tocamos nada del proveedor anterior hasta comprobar que todo funciona aquí.',
        ],
        [
            'q' => '¿Qué pasa si mi dominio está registrado en otro sitio?',
            'a' => 'No hay problema. Puedes apuntarlo a nuestros servidores y seguir renovándolo donde lo tienes, o trasladarlo con nosotros. En el panel queda registrado quién lo renueva, para que nunca haya dudas de quién debe pagarlo.',
        ],
        [
            'q' => '¿Emiten factura con IGV?',
            'a' => 'Sí. Emitimos boletas y facturas electrónicas ante SUNAT. Si necesitas factura, la solicitas desde tu portal indicando el RUC y la razón social; la recibes en PDF y XML.',
        ],
        [
            'q' => '¿Cómo pago mis servicios?',
            'a' => 'Por transferencia bancaria, Yape, Plin, izipay, PayPal o Culqi, según lo que tengas habilitado. En cada factura de tu portal aparecen los datos exactos para pagar y, cuando corresponde, un enlace de pago directo.',
        ],
        [
            'q' => '¿Qué pasa si no pago a tiempo?',
            'a' => 'Te avisamos antes del vencimiento y otra vez cuando la factura queda vencida. El servicio no se corta de inmediato: primero conversamos. Lo que sí conviene es no dejar vencer un dominio, porque su recuperación depende del registrador y suele costar más.',
        ],
        [
            'q' => '¿Puedo cambiar de plan más adelante?',
            'a' => 'Sí, en cualquier momento. Al subir de plan se ajusta la diferencia por el periodo que queda; al bajar, el cambio se aplica en la siguiente renovación.',
        ],
    ];
@endphp

<x-site-shell>

    {{-- Reglas de las pestañas de planes: se generan aquí porque las
         categorías vienen de la base de datos. --}}
    @if ($plansByCategory->isNotEmpty())
        <style>
            @foreach ($plansByCategory->keys() as $key)
                #zs-planes:has(#zs-tab-{{ $key }}:checked) [data-panel="{{ $key }}"] { display: block; }
            @endforeach
        </style>
    @endif

    {{-- ---------------------------------------------------------------- --}}
    {{-- Portada                                                          --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="relative overflow-hidden border-b border-line bg-gradient-to-b from-brand-50/70 via-white to-white">
        <div class="zs-container grid items-center gap-14 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <p class="ys-eyebrow">Hosting peruano</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-[1.08] tracking-tight text-ink sm:text-5xl lg:text-[3.4rem]">
                    Tu sitio en línea,<br>
                    <span class="text-brand-600">sin que tengas que ocuparte</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
                    Hosting, dominios y correo corporativo con soporte en español, facturación
                    electrónica y un portal donde ves todos tus servicios, vencimientos y pagos
                    en un solo lugar.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#planes" class="ys-btn ys-btn--primary !px-6 !py-3.5 text-base">
                        Ver planes y precios
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="{{ url('/client/login') }}" class="ys-btn ys-btn--outline !px-6 !py-3.5 text-base">
                        Entrar a mi cuenta
                    </a>
                </div>

                <ul class="mt-9 flex flex-wrap items-center gap-x-7 gap-y-3 text-sm font-medium text-ink-soft">
                    @foreach (['SSL incluido', 'Copias de seguridad', 'Factura electrónica', 'Migración gratuita'] as $point)
                        <li class="flex items-center gap-2">
                            <svg class="size-5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m4.5 12.5 5 5 10-11"/>
                            </svg>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Vista previa del portal del cliente: lo que realmente recibe
                 quien contrata, no una ilustración genérica. --}}
            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="ys-card !p-0 overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-xs font-extrabold text-white">
                            {{ \App\Support\Branding::initials() }}
                        </span>
                        <div>
                            <p class="text-sm font-bold text-ink">Mis servicios</p>
                            <p class="text-xs text-muted">Todo tu negocio, en una pantalla</p>
                        </div>
                    </div>

                    <div class="divide-y divide-line">
                        @foreach ([
                            ['name' => 'Hosting · miempresa.pe', 'meta' => 'Renueva el 14 de marzo', 'state' => 'Activo', 'tone' => 'bg-emerald-50 text-emerald-700'],
                            ['name' => 'Dominio · miempresa.pe', 'meta' => 'Renueva el 2 de abril', 'state' => 'Activo', 'tone' => 'bg-emerald-50 text-emerald-700'],
                            ['name' => 'Correo corporativo · 5 cuentas', 'meta' => 'Vence en 9 días', 'state' => 'Por vencer', 'tone' => 'bg-amber-50 text-amber-700'],
                        ] as $row)
                            <div class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">{{ $row['name'] }}</p>
                                    <p class="mt-0.5 text-xs text-muted">{{ $row['meta'] }}</p>
                                </div>
                                <span class="zp-badge {{ $row['tone'] }}">{{ $row['state'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-line bg-canvas px-6 py-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Por pagar</p>
                            <p class="text-lg font-extrabold text-ink">S/ 212.40</p>
                        </div>
                    </div>
                </div>

                {{-- Comprobante flotante. Va arriba a la derecha, que es la
                     única zona vacía de la tarjeta: abajo taparía el total. --}}
                <div class="absolute -top-6 -right-7 w-56 rounded-2xl border border-line bg-white p-4"
                    style="box-shadow: 0 18px 40px rgba(16,35,63,.14)">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 12.5 5 5 10-11"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-ink">F001-00042</p>
                            <p class="text-xs text-muted">Factura pagada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Servicios                                                        --}}
    {{-- ---------------------------------------------------------------- --}}
    <section id="servicios" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="zs-container">
            <p class="ys-eyebrow text-center">Qué ofrecemos</p>
            <h2 class="zs-section-title mt-3">Todo lo que tu sitio necesita</h2>
            <p class="zs-section-lead">
                Un solo proveedor para el alojamiento, el dominio, el correo y el mantenimiento.
                Una sola factura y un solo lugar al que escribir cuando algo pasa.
            </p>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <div class="ys-card ys-card--interactive flex flex-col">
                        <span class="ys-icon">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                {!! $service['icon'] !!}
                            </svg>
                        </span>

                        <h3 class="mt-5 text-lg font-bold text-ink">{{ $service['label'] }}</h3>

                        @if ($service['tagline'])
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-muted">{{ $service['tagline'] }}</p>
                        @endif

                        @if ($service['from'] !== null)
                            <p class="mt-5 text-sm text-muted">
                                Desde
                                <span class="text-base font-extrabold text-ink">S/ {{ number_format((float) $service['from'], 2) }}</span>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Planes                                                           --}}
    {{-- ---------------------------------------------------------------- --}}
    @if ($plansByCategory->isNotEmpty())
        <section id="planes" class="scroll-mt-20 border-y border-line bg-canvas py-20 sm:py-24">
            <div class="zs-container">
                <p class="ys-eyebrow text-center">Planes y precios</p>
                <h2 class="zs-section-title mt-3">Elige el plan que te queda</h2>
                <p class="zs-section-lead">
                    Precios en soles, con IGV incluido. Sin costos de instalación ni permanencia mínima:
                    puedes cambiar de plan cuando lo necesites.
                </p>

                <div id="zs-planes" class="mt-12">
                    {{-- Pestañas por categoría --}}
                    @if ($plansByCategory->count() > 1)
                        <div class="flex flex-wrap justify-center gap-3">
                            @foreach ($plansByCategory as $category => $plans)
                                <input type="radio" name="zs-plan-cat" id="zs-tab-{{ $category }}"
                                    class="zs-tabs__input" @checked($loop->first)>
                                <label for="zs-tab-{{ $category }}" class="zs-tabs__label">
                                    {{ Catalog::describe($category)['label'] }}
                                </label>
                            @endforeach
                        </div>
                    @else
                        @foreach ($plansByCategory as $category => $plans)
                            <input type="radio" name="zs-plan-cat" id="zs-tab-{{ $category }}" class="zs-tabs__input" checked>
                        @endforeach
                    @endif

                    {{-- Paneles --}}
                    @foreach ($plansByCategory as $category => $plans)
                        <div class="zs-tabs__panel mt-10" data-panel="{{ $category }}">
                            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                @foreach ($plans as $plan)
                                    {{-- Se destaca el segundo más barato: es el que casi
                                         siempre conviene y el que la gente termina eligiendo. --}}
                                    @php $featured = $plans->count() > 2 && $loop->index === 1; @endphp

                                    <div @class(['zs-plan', 'zs-plan--featured' => $featured])>
                                        @if ($featured)
                                            <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">
                                                Más elegido
                                            </span>
                                        @endif

                                        <h3 class="text-base font-bold text-ink">{{ $plan->name }}</h3>

                                        <p class="mt-4 flex items-baseline gap-1.5">
                                            <span class="zs-plan__price">S/ {{ number_format((float) $plan->price, 0) }}</span>
                                            <span class="zs-plan__cycle">{{ Catalog::cycleSuffix($plan->billing_cycle) }}</span>
                                        </p>

                                        {{-- Altura fija de dos líneas aunque no haya
                                             descripción: si no, los botones «Contratar»
                                             quedan a distinta altura en cada tarjeta y la
                                             fila de precios se ve descuadrada. --}}
                                        <p class="mt-3 line-clamp-2 min-h-11 text-sm leading-relaxed text-muted">
                                            {{ $plan->description }}
                                        </p>

                                        <a href="{{ url('/contratar') }}"
                                            @class([
                                                'ys-btn mt-6 w-full',
                                                'ys-btn--primary' => $featured,
                                                'ys-btn--outline' => ! $featured,
                                            ])>
                                            Contratar
                                        </a>

                                        @php $features = $plan->planFeatures(); @endphp
                                        @if ($features)
                                            <div class="mt-6 border-t border-line pt-2">
                                                @foreach ($features as $feature)
                                                    <div class="zs-feature">
                                                        <span class="text-muted">{{ $feature['label'] }}</span>
                                                        <span class="text-right font-semibold text-ink">{{ $feature['value'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="mt-10 text-center text-sm text-muted">
                    ¿Ninguno te encaja?
                    <a href="{{ url('/contratar') }}" class="ys-link">Cuéntanos qué necesitas</a>
                </p>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------------- --}}
    {{-- Dominios                                                         --}}
    {{-- ---------------------------------------------------------------- --}}
    <section id="dominios" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="zs-container">
            <p class="ys-eyebrow text-center">Dominios</p>
            <h2 class="zs-section-title mt-3">Tu nombre en internet</h2>
            <p class="zs-section-lead">
                Registra uno nuevo o trae el que ya tienes. Nosotros lo renovamos a tiempo
                y te avisamos antes de cada vencimiento.
            </p>

            {{-- No consultamos disponibilidad en tiempo real: el formulario
                 lleva el nombre al alta del servicio y lo revisamos nosotros.
                 Prometer un "disponible" que no comprobamos sería mentir. --}}
            <form action="{{ url('/contratar') }}" method="GET"
                class="mx-auto mt-10 flex max-w-2xl flex-col gap-3 sm:flex-row">
                <label for="dominio" class="sr-only">Nombre del dominio que buscas</label>
                <input type="text" name="dominio" id="dominio" class="zp-input flex-1 !py-3.5"
                    placeholder="miempresa.pe" autocomplete="off">
                <button type="submit" class="ys-btn ys-btn--primary !px-7 !py-3.5 shrink-0">
                    Consultar
                </button>
            </form>
            <p class="mt-3 text-center text-xs text-muted">
                Revisamos la disponibilidad y te confirmamos por correo o WhatsApp.
            </p>

            @if ($domains->isNotEmpty())
                <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($domains as $domain)
                        <div class="ys-card ys-card--interactive !p-6 text-center">
                            <p class="text-2xl font-extrabold tracking-tight text-brand-600">{{ $domain->name }}</p>
                            <p class="mt-3 text-2xl font-extrabold text-ink">
                                S/ {{ number_format((float) $domain->price, 0) }}
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                {{ Catalog::cycleSuffix($domain->billing_cycle) ?: 'por periodo' }}
                                @if ($domain->is_renewable) · renovable @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-12 rounded-2xl border border-line bg-canvas p-7 sm:p-9">
                <div class="grid gap-7 sm:grid-cols-3">
                    @foreach ([
                        ['t' => '¿Ya tienes dominio?', 'b' => 'Puedes apuntarlo a nuestros servidores sin trasladarlo. Seguirás renovándolo donde lo tienes.'],
                        ['t' => '¿Quieres traerlo?', 'b' => 'Gestionamos el traslado completo. A partir de ahí lo renovamos nosotros y lo ves en tu portal.'],
                        ['t' => '¿Es nuevo?', 'b' => 'Lo registramos a tu nombre. El dominio es tuyo, no nuestro, y así queda documentado.'],
                    ] as $card)
                        <div>
                            <h3 class="text-base font-bold text-ink">{{ $card['t'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $card['b'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Por qué nosotros                                                 --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="border-y border-line bg-canvas py-20 sm:py-24">
        <div class="zs-container">
            <p class="ys-eyebrow text-center">Por qué {{ $brandName }}</p>
            <h2 class="zs-section-title mt-3">Lo que va incluido en todos los planes</h2>
            <p class="zs-section-lead">
                Sin letra pequeña ni servicios que se cobran aparte cuando ya los necesitas.
            </p>

            <div class="mt-14 grid gap-x-10 gap-y-11 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($reasons as $reason)
                    <div class="flex gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 ring-1 ring-line">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                {!! $reason['icon'] !!}
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-ink">{{ $reason['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $reason['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Soporte                                                          --}}
    {{-- ---------------------------------------------------------------- --}}
    <section id="soporte" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="zs-container">
            <p class="ys-eyebrow text-center">Soporte</p>
            <h2 class="zs-section-title mt-3">Preguntas frecuentes</h2>
            <p class="zs-section-lead">
                Y si la tuya no está aquí, escríbenos: respondemos personas, no formularios.
            </p>

            <div class="mx-auto mt-12 max-w-3xl space-y-4">
                @foreach ($faqs as $faq)
                    <details class="zs-faq">
                        <summary>
                            {{ $faq['q'] }}
                            <svg class="size-5 shrink-0 text-muted transition-transform duration-200" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <p class="border-t border-line px-6 py-5 text-sm leading-relaxed text-muted">
                            {{ $faq['a'] }}
                        </p>
                    </details>
                @endforeach
            </div>

            <div class="mx-auto mt-12 max-w-3xl rounded-2xl border border-line bg-canvas p-7 text-center sm:p-9">
                <h3 class="text-lg font-bold text-ink">¿Ya eres cliente?</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-muted">
                    Abre un ticket desde tu portal y queda registrado con todo el historial de tu servicio,
                    para que no tengas que explicar tu caso desde cero cada vez.
                </p>
                <a href="{{ url('/client/tickets') }}" class="ys-btn ys-btn--primary mt-6 !px-6 !py-3.5">
                    Abrir un ticket
                </a>
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Cierre                                                           --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="bg-ink py-20 sm:py-24">
        <div class="zs-container text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                ¿Empezamos?
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/70">
                Cuéntanos qué necesitas y te decimos qué plan te conviene. Si vienes de otro
                proveedor, la migración corre por nuestra cuenta.
            </p>
            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <a href="{{ url('/contratar') }}" class="ys-btn !bg-white !px-6 !py-3.5 text-base !text-ink hover:!bg-white/90">
                    Contratar un servicio
                </a>
                <a href="#planes" class="ys-btn !px-6 !py-3.5 text-base !text-white ring-1 ring-white/25 hover:!bg-white/10">
                    Volver a los planes
                </a>
            </div>
        </div>
    </section>

</x-site-shell>
