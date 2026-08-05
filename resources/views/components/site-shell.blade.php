@props([
    'title' => null,
    'description' => 'Hosting, dominios, correo corporativo y soporte técnico en español para empresas y agencias del Perú.',
])

@php
    $brandName = \App\Support\Branding::name();
    $brandLogo = \App\Support\Branding::logoUrl();
    $brandFavicon = \App\Support\Branding::faviconUrl();
    $brandColor = \App\Support\Branding::color();

    $sections = [
        ['label' => 'Servicios', 'href' => '#servicios'],
        ['label' => 'Planes', 'href' => '#planes'],
        ['label' => 'Dominios', 'href' => '#dominios'],
        ['label' => 'Soporte', 'href' => '#soporte'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$brandName : $brandName.' · Hosting y dominios en Perú' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="{{ $title ?? $brandName }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    @if ($brandFavicon)
        <link rel="icon" href="{{ $brandFavicon }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>:root { --color-brand-600: {{ $brandColor }}; }</style>
</head>
<body class="min-h-full bg-white">

    {{-- Barra superior --}}
    <header class="sticky top-0 z-40 border-b border-line/80 bg-white/85 backdrop-blur">
        <div class="zs-container flex h-16 items-center gap-6">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2.5">
                @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 w-auto max-w-[10rem] object-contain">
                @else
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-extrabold text-white">
                        {{ \App\Support\Branding::initials() }}
                    </span>
                    <span class="text-lg font-extrabold tracking-tight text-ink">{{ $brandName }}</span>
                @endif
            </a>

            <nav class="hidden flex-1 items-center gap-1 md:flex">
                @foreach ($sections as $section)
                    <a href="{{ $section['href'] }}" class="zs-navlink">{{ $section['label'] }}</a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2">
                {{-- El panel de administración es para el equipo, no para
                     visitantes; se enseña discreto y solo en pantallas
                     anchas, pero sin esconderlo. --}}
                <a href="{{ url('/admin') }}" class="hidden items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-muted transition hover:bg-slate-100 hover:text-ink lg:inline-flex">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 4 6.5V12c0 5 3.4 8.2 8 9 4.6-.8 8-4 8-9V6.5L12 3z"/>
                    </svg>
                    Administración
                </a>
                <a href="{{ url('/client/login') }}" class="ys-btn ys-btn--primary !px-4 !py-2.5">
                    Área de clientes
                </a>
            </div>
        </div>

        {{-- Navegación en móvil --}}
        <nav class="flex items-center gap-1 overflow-x-auto border-t border-line px-4 py-2 md:hidden">
            @foreach ($sections as $section)
                <a href="{{ $section['href'] }}" class="zs-navlink shrink-0">{{ $section['label'] }}</a>
            @endforeach
            <a href="{{ url('/admin') }}" class="zs-navlink shrink-0">Administración</a>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-ink text-white/70">
        <div class="zs-container grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-2.5">
                    @if ($brandLogo)
                        <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 w-auto max-w-[10rem] object-contain brightness-0 invert">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-xl bg-white/10 text-sm font-extrabold text-white">
                            {{ \App\Support\Branding::initials() }}
                        </span>
                        <span class="text-lg font-extrabold tracking-tight text-white">{{ $brandName }}</span>
                    @endif
                </div>
                <p class="mt-4 text-sm leading-relaxed">
                    Hosting, dominios y soporte técnico en español. Facturación con IGV y comprobantes electrónicos.
                </p>
            </div>

            <div>
                <p class="zs-foot-title">Servicios</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#servicios" class="zs-footlink">Hosting web</a></li>
                    <li><a href="#dominios" class="zs-footlink">Dominios</a></li>
                    <li><a href="#servicios" class="zs-footlink">Correo corporativo</a></li>
                    <li><a href="#planes" class="zs-footlink">Planes y precios</a></li>
                </ul>
            </div>

            <div>
                <p class="zs-foot-title">Ayuda</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="#soporte" class="zs-footlink">Preguntas frecuentes</a></li>
                    <li><a href="{{ url('/client/tickets') }}" class="zs-footlink">Abrir un ticket</a></li>
                    <li><a href="{{ url('/contratar') }}" class="zs-footlink">Contratar un servicio</a></li>
                </ul>
            </div>

            <div>
                <p class="zs-foot-title">Acceso</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ url('/client/login') }}" class="zs-footlink">Área de clientes</a></li>
                    <li><a href="{{ url('/admin') }}" class="zs-footlink">Panel de administración</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="zs-container flex flex-col gap-2 py-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ now()->year }} {{ $brandName }}. Todos los derechos reservados.</p>
                <p>Precios en soles. Incluyen IGV salvo que se indique lo contrario.</p>
            </div>
        </div>
    </footer>
</body>
</html>
