@props([
    'eyebrow' => 'zupraHost',
    'heading' => '',
    'step' => null,
    'backUrl' => null,
    'nextLabel' => null,
    'nextUrl' => '#',
])

<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading ? $heading.' · ' : '' }}zupraHost</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-full flex-col">
    {{-- Barra superior --}}
    <header class="sticky top-0 z-10 border-b border-line bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-6">
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="ys-btn-round" aria-label="Volver">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </a>
            @endif
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $eyebrow }}</p>
                <h1 class="truncate text-sm font-bold text-ink">{{ $heading }}</h1>
            </div>
        </div>
    </header>

    {{-- Contenido --}}
    <main class="flex-1">
        <div class="mx-auto max-w-3xl px-6 py-16 sm:py-20">
            {{ $slot }}
        </div>
    </main>

    {{-- Pie con paso y acción --}}
    <footer class="sticky bottom-0 border-t border-line bg-white">
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-6">
            <span class="text-sm text-muted">
                {{ $step }}
                <span class="ml-2 hidden text-xs text-slate-400 sm:inline">· Desarrollado por MachaData</span>
            </span>
            @if ($nextLabel)
                <a href="{{ $nextUrl }}" class="ys-btn ys-btn--primary">
                    {{ $nextLabel }}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </a>
            @endif
        </div>
    </footer>
</body>
</html>
