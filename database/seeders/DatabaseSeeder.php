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
            ['email' => 'admin@yachay.lat'],
            ['name' => 'Administrador', 'password' => Hash::make('password')],
        );
        $admin->assignRole('Administrador');

        // Cliente de demostración con acceso al portal.
        $clientUser = User::firstOrCreate(
            ['email' => 'cliente@yachay.lat'],
            ['name' => 'Cliente Demo', 'password' => Hash::make('password')],
        );
        $clientUser->assignRole('Cliente');

        Client::firstOrCreate(
            ['email' => 'cliente@yachay.lat'],
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
