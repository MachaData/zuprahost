<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Administrador principal.
        $admin = User::firstOrCreate(
            ['email' => 'admin@zuprahost.com'],
            ['name' => 'Administrador', 'password' => Hash::make('password')],
        );
        $admin->assignRole('Administrador');

        // Cliente de demostración con acceso al portal.
        $clientUser = User::firstOrCreate(
            ['email' => 'cliente@zuprahost.com'],
            ['name' => 'Cliente Demo', 'password' => Hash::make('password')],
        );
        $clientUser->assignRole('Cliente');

        Client::firstOrCreate(
            ['email' => 'cliente@zuprahost.com'],
            [
                'user_id' => $clientUser->id,
                'type' => 'natural',
                'name' => 'Cliente Demo',
                'document_type' => 'dni',
                'document_number' => '12345678',
                'status' => 'activo',
                'country' => 'Perú',
            ],
        );
    }
}
