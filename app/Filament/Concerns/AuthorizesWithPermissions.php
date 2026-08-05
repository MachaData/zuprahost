<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Aplica al panel los permisos que ya define RolePermissionSeeder.
 *
 * Estaban creados y asignados a cada rol, pero nada los comprobaba: Soporte
 * podía aprobar pagos y Ventas borrar clientes. El recurso declara a qué
 * permiso responde y de ahí salen las cuatro respuestas de Filament.
 */
trait AuthorizesWithPermissions
{
    /**
     * Nombre del permiso, sin el verbo: 'invoice' cubre view/create/update/delete invoice.
     */
    abstract public static function permissionName(): string;

    protected static function allows(string $ability): bool
    {
        return auth()->user()?->can("{$ability} ".static::permissionName()) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::allows('view');
    }

    public static function canView(Model $record): bool
    {
        return static::allows('view');
    }

    public static function canCreate(): bool
    {
        return static::allows('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::allows('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::allows('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::allows('delete');
    }
}
