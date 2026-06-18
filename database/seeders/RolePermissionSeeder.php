<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resources = [
            'client', 'product', 'service', 'domain', 'hosting',
            'invoice', 'payment', 'ticket', 'license', 'report', 'setting', 'user',
        ];
        $abilities = ['view', 'create', 'update', 'delete'];

        foreach ($resources as $resource) {
            foreach ($abilities as $ability) {
                Permission::findOrCreate("{$ability} {$resource}");
            }
        }

        // Administrador: todos los permisos.
        $admin = Role::findOrCreate('Administrador');
        $admin->syncPermissions(Permission::all());

        // Soporte: tickets + lectura de servicios/clientes, sin facturación crítica.
        $soporte = Role::findOrCreate('Soporte');
        $soporte->syncPermissions([
            'view client', 'view service', 'view domain', 'view hosting',
            'view ticket', 'create ticket', 'update ticket',
            'view license', 'view product',
        ]);

        // Ventas: crea clientes y servicios/órdenes.
        $ventas = Role::findOrCreate('Ventas');
        $ventas->syncPermissions([
            'view client', 'create client', 'update client',
            'view product', 'view service', 'create service', 'update service',
            'view invoice', 'create invoice', 'view domain', 'view hosting',
        ]);

        // Facturación: gestiona facturas y pagos.
        $facturacion = Role::findOrCreate('Facturación');
        $facturacion->syncPermissions([
            'view client', 'view invoice', 'create invoice', 'update invoice', 'delete invoice',
            'view payment', 'create payment', 'update payment',
            'view service', 'view report',
        ]);

        // Cliente: rol de portal (sin permisos de admin).
        Role::findOrCreate('Cliente');
    }
}
