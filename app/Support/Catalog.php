<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Cómo se presenta el catálogo en el sitio público.
 *
 * Los productos y sus precios viven en la base de datos y los edita el
 * administrador; aquí solo está lo que no cabe en una tabla: el icono de cada
 * categoría, su texto de venta y en qué orden se enseñan. Una categoría sin
 * ficha aquí igual aparece, con el icono genérico.
 */
class Catalog
{
    /**
     * Ficha de presentación por categoría. El orden de este array es el orden
     * en que se muestran las secciones y las pestañas de planes.
     *
     * @var array<string, array{label: string, tagline: string, icon: string}>
     */
    public const PRESENTATION = [
        'hosting' => [
            'label' => 'Hosting web',
            'tagline' => 'Alojamiento rápido y estable para tu sitio o tienda, con copias de seguridad y soporte en español.',
            'icon' => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5h.01M7 16.5h.01"/>',
        ],
        'dominio' => [
            'label' => 'Dominios',
            'tagline' => 'Registra o traslada tu .com, .pe o .com.pe. Nosotros nos encargamos de renovarlo a tiempo.',
            'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"/>',
        ],
        'correo' => [
            'label' => 'Correo corporativo',
            'tagline' => 'Buzones con tu propio dominio. Se ven profesionales y llegan a la bandeja de entrada.',
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7.5 9 6 9-6"/>',
        ],
        'vps' => [
            'label' => 'Servidores VPS',
            'tagline' => 'Recursos dedicados para proyectos que ya no caben en un hosting compartido.',
            'icon' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h4"/>',
        ],
        'ssl' => [
            'label' => 'Certificados SSL',
            'tagline' => 'El candado en la barra del navegador: tus visitantes confían y Google te posiciona mejor.',
            'icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        ],
        'mantenimiento' => [
            'label' => 'Mantenimiento WordPress',
            'tagline' => 'Actualizaciones, copias y revisión de seguridad cada mes. Tú te dedicas a tu negocio.',
            'icon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5z"/>',
        ],
        'licencia' => [
            'label' => 'Licencias',
            'tagline' => 'Plugins y temas originales, con actualizaciones y soporte del fabricante.',
            'icon' => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2"/><path d="m17 6 2 2M15 8l2 2"/>',
        ],
        'seo' => [
            'label' => 'SEO mensual',
            'tagline' => 'Trabajo continuo de posicionamiento, con un reporte claro de qué se hizo y qué cambió.',
            'icon' => '<path d="M3 3v18h18"/><path d="m7 15 3-4 3 2 4-6"/>',
        ],
        'desarrollo' => [
            'label' => 'Desarrollo web',
            'tagline' => 'Sitios y tiendas a medida, entregados listos para vender.',
            'icon' => '<path d="m8 9-3 3 3 3"/><path d="m16 9 3 3-3 3"/><path d="m13.5 7-3 10"/>',
        ],
        'migracion' => [
            'label' => 'Migraciones',
            'tagline' => 'Traemos tu sitio desde otro proveedor sin que se caiga ni pierdas correos.',
            'icon' => '<path d="M3 12h13"/><path d="m12 7 5 5-5 5"/><path d="M21 5v14"/>',
        ],
        'backup' => [
            'label' => 'Copias de seguridad',
            'tagline' => 'Respaldos automáticos y restauración cuando algo sale mal.',
            'icon' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        ],
        'soporte' => [
            'label' => 'Soporte técnico',
            'tagline' => 'Ayuda por ticket y WhatsApp, en español y sin respuestas de robot.',
            'icon' => '<path d="M18 12a6 6 0 1 0-12 0"/><rect x="3" y="12" width="4" height="7" rx="1.5"/><rect x="17" y="12" width="4" height="7" rx="1.5"/><path d="M19 19a3 3 0 0 1-3 3h-2"/>',
        ],
    ];

    public const FALLBACK_ICON = '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 12h6"/>';

    /** Categorías que se muestran como planes con precio en la portada. */
    public const PLAN_CATEGORIES = ['hosting', 'vps', 'correo', 'mantenimiento'];

    /**
     * Productos activos con precio, agrupados por categoría y en el orden de
     * PRESENTATION. Solo las categorías que tienen algo que vender.
     *
     * @return Collection<string, Collection<int, Product>>
     */
    public static function plansByCategory(): Collection
    {
        return static::sortByPresentation(
            static::safely(fn () => Product::query()
                ->where('is_active', true)
                ->whereIn('category', self::PLAN_CATEGORIES)
                ->orderBy('price')
                ->get(), collect())
                ->groupBy('category')
        );
    }

    /**
     * Los dominios se venden por extensión, no por plan: se listan aparte y
     * ordenados por precio, que es como los compara la gente.
     *
     * @return Collection<int, Product>
     */
    public static function domains(): Collection
    {
        return static::safely(fn () => Product::query()
            ->where('is_active', true)
            ->where('category', 'dominio')
            ->orderBy('price')
            ->get(), collect());
    }

    /**
     * Las categorías que el negocio ofrece de verdad: las que tienen al menos
     * un producto activo. Si el catálogo está vacío se enseña la lista
     * completa, para que la portada no aparezca desnuda recién instalada.
     *
     * @return array<int, array{key: string, label: string, tagline: string, icon: string, from: float|null}>
     */
    public static function services(): array
    {
        $cheapest = static::safely(fn () => Product::query()
            ->where('is_active', true)
            ->selectRaw('category, min(price) as from_price')
            ->groupBy('category')
            ->pluck('from_price', 'category'), collect());

        $keys = $cheapest->isEmpty()
            ? array_keys(self::PRESENTATION)
            : $cheapest->keys()->all();

        $services = [];

        foreach (array_keys(self::PRESENTATION) as $key) {
            if (! in_array($key, $keys, true)) {
                continue;
            }

            $services[] = static::describe($key) + ['from' => $cheapest[$key] ?? null];
        }

        // Categorías con productos pero sin ficha de presentación: se muestran
        // igual, para que nada de lo que se vende quede invisible.
        foreach ($keys as $key) {
            if (! array_key_exists($key, self::PRESENTATION)) {
                $services[] = static::describe($key) + ['from' => $cheapest[$key] ?? null];
            }
        }

        return $services;
    }

    /**
     * @return array{key: string, label: string, tagline: string, icon: string}
     */
    public static function describe(string $key): array
    {
        $presentation = self::PRESENTATION[$key] ?? null;

        return [
            'key' => $key,
            'label' => $presentation['label'] ?? ucfirst($key),
            'tagline' => $presentation['tagline'] ?? '',
            'icon' => $presentation['icon'] ?? self::FALLBACK_ICON,
        ];
    }

    /** Etiqueta legible del ciclo de cobro, para poner junto al precio. */
    public static function cycleSuffix(?string $cycle): string
    {
        return match ($cycle) {
            'mensual' => '/mes',
            'anual' => '/año',
            default => '',
        };
    }

    /**
     * La portada es lo primero que ve un cliente potencial y lo primero que
     * mira quien está evaluando si contratarnos. Si la base de datos falla,
     * pierde los precios pero sigue en pie: quién somos, qué hacemos y cómo
     * contactarnos no dependen de una consulta.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $query
     * @param  TValue  $fallback
     * @return TValue
     */
    protected static function safely(callable $query, mixed $fallback): mixed
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            report($e);

            return $fallback;
        }
    }

    /**
     * @param  Collection<string, Collection<int, Product>>  $grouped
     * @return Collection<string, Collection<int, Product>>
     */
    protected static function sortByPresentation(Collection $grouped): Collection
    {
        $order = array_flip(array_keys(self::PRESENTATION));

        return $grouped->sortBy(fn ($products, string $category) => $order[$category] ?? 999);
    }
}
