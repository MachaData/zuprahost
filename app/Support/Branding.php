<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Marca de la empresa: nombre, logo, favicon y color.
 *
 * Se lee de la tabla `settings`, pero los paneles de Filament la consultan
 * mientras se construyen —incluso durante `artisan migrate` sobre una base
 * vacía—, así que cada acceso está protegido: si la tabla aún no existe se
 * devuelve el valor por defecto en lugar de reventar el arranque.
 */
class Branding
{
    public const KEYS = [
        'brand_name',
        'brand_logo',
        'brand_favicon',
        'brand_color',
    ];

    public const DEFAULT_NAME = 'zupraHost';

    public const DEFAULT_COLOR = '#2557e6';

    /** Nombre comercial que se muestra en paneles, portal y comprobantes. */
    public static function name(): string
    {
        return static::value('brand_name') ?: self::DEFAULT_NAME;
    }

    /** Iniciales para el cuadrito de marca cuando no hay logo cargado. */
    public static function initials(): string
    {
        return mb_strtoupper(mb_substr(static::name(), 0, 1));
    }

    /** URL pública del logo, o null si no se ha subido ninguno. */
    public static function logoUrl(): ?string
    {
        return static::fileUrl('brand_logo');
    }

    /** URL pública del favicon, o null si no se ha subido ninguno. */
    public static function faviconUrl(): ?string
    {
        return static::fileUrl('brand_favicon');
    }

    /** Color primario en hexadecimal. Se valida para no inyectar CSS roto. */
    public static function color(): string
    {
        $color = static::value('brand_color');

        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color)
            ? $color
            : self::DEFAULT_COLOR;
    }

    /**
     * Ruta absoluta del logo en disco, para dompdf: el generador de PDF no
     * descarga URLs, necesita el archivo.
     */
    public static function logoPath(): ?string
    {
        $path = static::value('brand_logo');

        if (! $path) {
            return null;
        }

        // DomPDF solo dibuja estos formatos; con un WebP rompería el PDF
        // entero en lugar de omitir la imagen.
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg'], true)) {
            return null;
        }

        try {
            $absolute = Storage::disk('public')->path($path);
        } catch (Throwable) {
            return null;
        }

        return is_file($absolute) ? $absolute : null;
    }

    protected static function fileUrl(string $key): ?string
    {
        $path = static::value($key);

        if (! $path) {
            return null;
        }

        try {
            return Storage::disk('public')->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    protected static function value(string $key): ?string
    {
        try {
            $value = Setting::get($key);
        } catch (Throwable) {
            // Tabla inexistente o base no disponible: la marca no es motivo
            // para tumbar la petición.
            return null;
        }

        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }
}
