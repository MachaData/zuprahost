@props([
    'client' => null,
    'active' => 'panel',
])

@php
    $nav = [
        ['key' => 'panel',    'label' => 'Inicio',            'url' => url('/panel'),            'icon' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>'],
        ['key' => 'renewals', 'label' => 'Renovaciones',      'url' => url('/panel/renovaciones'), 'icon' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>'],
        ['key' => 'services', 'label' => 'Mis servicios',    'url' => url('/panel/servicios'),  'icon' => '<rect x="3" y="4" width="18" height="8" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 8h.01M7 17h.01"/>'],
        ['key' => 'domains',  'label' => 'Dominios',          'url' => url('/panel/dominios'),   'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"/>'],
        ['key' => 'payments', 'label' => 'Pagos',              'url' => url('/panel/pagos'),      'icon' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>'],
        ['key' => 'tickets',  'label' => 'Soporte',           'url' => url('/client/tickets'),   'icon' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>'],
        ['key' => 'licenses', 'label' => 'Licencias',         'url' => url('/panel/licencias'),  'icon' => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2"/><path d="m17 6 2 2M15 8l2 2"/>'],
        ['key' => 'billing',  'label' => 'Facturación',       'url' => url('/client/billing-profile'), 'icon' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>'],
    ];

    $brandName = \App\Support\Branding::name();
    $brandLogo = \App\Support\Branding::logoUrl();
    $brandFavicon = \App\Support\Branding::faviconUrl();
    $brandColor = \App\Support\Branding::color();
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Mi panel' }} · {{ $brandName }}</title>
    @if ($brandFavicon)
        <link rel="icon" href="{{ $brandFavicon }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    {{-- El color de marca se define en Configuración y pisa el valor del tema. --}}
    <style>:root { --color-brand-600: {{ $brandColor }}; }</style>
</head>
<body class="min-h-full">
    <div class="flex min-h-screen">
        {{-- Barra lateral --}}
        <aside class="hidden w-64 shrink-0 flex-col border-r border-line bg-white px-4 py-5 lg:flex">
            <div class="flex items-center gap-2 px-2">
                @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-9 w-auto max-w-[11rem] object-contain">
                @else
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-extrabold text-white">{{ \App\Support\Branding::initials() }}</span>
                    <span class="text-lg font-extrabold tracking-tight text-ink">{{ $brandName }}</span>
                @endif
            </div>

            {{-- Cuenta --}}
            <div class="mt-6 rounded-xl border border-line bg-canvas px-3 py-2.5">
                <p class="text-[11px] uppercase tracking-wide text-muted">Cuenta</p>
                <p class="truncate text-sm font-semibold text-ink">{{ $client?->name ?? 'Mi cuenta' }}</p>
            </div>

            {{-- Navegación --}}
            <nav class="mt-6 flex flex-1 flex-col gap-1">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" @class(['zp-nav', 'zp-nav--active' => $active === $item['key']])>
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Salir --}}
            <form method="POST" action="{{ url('/client/logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="zp-nav w-full text-left text-muted hover:bg-red-50 hover:text-red-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </aside>

        {{-- Contenido --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Barra superior --}}
            <header class="flex h-16 items-center justify-between border-b border-line bg-white px-6">
                <div class="flex items-center gap-2 lg:hidden">
                    @if ($brandLogo)
                        <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 w-auto max-w-[9rem] object-contain">
                    @else
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-600 text-xs font-extrabold text-white">{{ \App\Support\Branding::initials() }}</span>
                        <span class="font-extrabold text-ink">{{ $brandName }}</span>
                    @endif
                </div>
                <p class="hidden text-sm text-muted lg:block">{{ $client?->email }}</p>
                <a href="{{ url('/contratar') }}" class="ys-btn ys-btn--primary !px-4 !py-2.5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Contratar servicio
                </a>
            </header>

            <main class="flex-1 bg-canvas px-6 py-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
