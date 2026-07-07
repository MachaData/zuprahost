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

        $client = Client::firstOrCreate(
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

        $this->seedDemoContent($client);
    }

    /**
     * Datos de ejemplo para que el panel del cliente muestre contenido real.
     */
    protected function seedDemoContent(Client $client): void
    {
        if ($client->services()->exists()) {
            return;
        }

        $hostingProduct = \App\Models\Product::firstOrCreate(
            ['name' => 'Hosting Emprende'],
            ['category' => 'hosting', 'billing_cycle' => 'anual', 'price' => 180, 'cost' => 60],
        );
        \App\Models\Product::firstOrCreate(
            ['name' => 'Dominio .com'],
            ['category' => 'dominio', 'billing_cycle' => 'anual', 'price' => 60, 'cost' => 35],
        );

        $domain = $client->domains()->create([
            'name' => 'miempresa.com',
            'provider' => 'zupraHost',
            'registered_at' => now()->subMonths(10),
            'expires_at' => now()->addDays(20),
            'renewal_price' => 60,
            'status' => 'por_vencer',
        ]);

        $client->services()->create([
            'product_id' => $hostingProduct->id,
            'domain_id' => $domain->id,
            'name' => 'Hosting Emprende · miempresa.com',
            'price' => 180,
            'billing_cycle' => 'anual',
            'starts_at' => now()->subMonths(10),
            'ends_at' => now()->addDays(20),
            'next_renewal_at' => now()->addDays(20),
            'status' => 'activo',
        ]);

        $client->services()->create([
            'name' => 'Certificado SSL',
            'price' => 90,
            'billing_cycle' => 'anual',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->addMonths(10),
            'status' => 'activo',
        ]);

        $invoice = \App\Models\Invoice::create([
            'client_id' => $client->id,
            'code' => \App\Models\Invoice::generateCode(),
            'issued_at' => now()->subDays(2),
            'due_at' => now()->addDays(5),
            'status' => 'pendiente',
        ]);
        $invoice->items()->create([
            'description' => 'Renovación Hosting Emprende · miempresa.com',
            'quantity' => 1,
            'unit_price' => 180,
        ]);
        $invoice->recalculateTotals();
    }
}
