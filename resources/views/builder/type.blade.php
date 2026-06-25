@php
    // Datos de las dos opciones (programa / curso). En limpio para leerse fácil.
    $options = [
        [
            'key' => 'programa',
            'title' => 'Programa',
            'lead' => 'Diplomado o especialización que agrupa <strong class="font-semibold text-ink">varios cursos</strong>. El alumno matriculado hereda acceso a todos.',
            'steps' => ['Lo esencial', 'Cursos', 'Publicar'],
            'cta' => 'Crear programa',
            'url' => '#', // Paso 2 (Cursos) aún no construido
            'icon' => '<path d="M6 3v12"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
        ],
        [
            'key' => 'curso',
            'title' => 'Curso',
            'lead' => 'Contenido independiente que se vende y construye solo. Va directo a <strong class="font-semibold text-ink">lecciones</strong>, quizzes y tareas.',
            'steps' => ['Lo esencial', 'Temario', 'Publicar'],
            'cta' => 'Crear curso',
            'url' => '#', // Paso 2 (Temario) aún no construido
            'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        ],
    ];
@endphp

<x-builder-shell
    heading="Pináculo Numerológico y Sinastría"
    step="Paso 1 de 3 · Lo esencial"
    :backUrl="url('/')"
    nextLabel="Siguiente: Temario"
    nextUrl="#"
>
    {{-- Encabezado --}}
    <div class="max-w-xl">
        <p class="ys-eyebrow">Nuevo en el catálogo</p>
        <h2 class="ys-title mt-3">¿Qué deseas crear?</h2>
        <p class="ys-lead mt-3">
            Elige el tipo de contenido. Un programa agrupa varios cursos (cada uno con sus
            lecciones); un curso es contenido independiente que se construye directamente
            con lecciones.
        </p>
    </div>

    {{-- Opciones --}}
    <div class="mt-10 grid gap-6 sm:grid-cols-2">
        @foreach ($options as $option)
            <a href="{{ $option['url'] }}" class="ys-card ys-card--interactive group flex flex-col">
                <span class="ys-icon">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        {!! $option['icon'] !!}
                    </svg>
                </span>

                <h3 class="mt-5 text-lg font-bold text-ink">{{ $option['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-muted">{!! $option['lead'] !!}</p>

                {{-- Ruta de pasos --}}
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @foreach ($option['steps'] as $i => $stepLabel)
                        <span class="ys-pill">{{ $stepLabel }}</span>
                        @if (! $loop->last)
                            <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        @endif
                    @endforeach
                </div>

                <span class="ys-link mt-6">
                    {{ $option['cta'] }}
                    <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </span>
            </a>
        @endforeach
    </div>
</x-builder-shell>
