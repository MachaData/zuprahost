<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un enlace de acceso de un servicio. Nunca guarda contraseñas: dice dónde
 * entrar y con qué usuario, y el resto se entrega por soporte.
 */
class ServiceAccess extends Model
{
    protected $table = 'service_accesses';

    protected $fillable = [
        'service_id', 'type', 'label', 'url', 'username',
        'instructions', 'is_visible', 'sort',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Nombre y icono por tipo. La clave es el enum de la tabla; el icono es
     * el contenido de un <svg> de 24×24 con trazo.
     */
    public const TYPES = [
        'directadmin' => 'DirectAdmin',
        'cpanel' => 'cPanel',
        'plesk' => 'Plesk',
        'webmail' => 'Webmail',
        'wordpress' => 'WordPress',
        'vps' => 'Consola del VPS',
        'ftp' => 'FTP / SFTP',
        'base_datos' => 'Base de datos',
        'licencia' => 'Portal de licencias',
        'otro' => 'Otro acceso',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Etiqueta a mostrar: la personalizada si la hay, si no la del tipo.
     */
    public function displayLabel(): string
    {
        return $this->label ?: (self::TYPES[$this->type] ?? 'Acceso');
    }

    public function icon(): string
    {
        return match ($this->type) {
            'webmail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'vps' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 9 3 3-3 3M13 15h4"/>',
            'ftp' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
            'base_datos' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
            'licencia' => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2"/><path d="m17 6 2 2M15 8l2 2"/>',
            'wordpress' => '<circle cx="12" cy="12" r="9"/><path d="m5 8 4 10 3-8 3 8 4-10"/>',
            default => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 9h18"/><path d="M7 14h5"/>',
        };
    }
}
