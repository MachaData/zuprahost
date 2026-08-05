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
        // Este seeder crea contenido de demostración con contraseñas conocidas.
        // Ejecutarlo contra la base real dejaría dos cuentas abiertas con la
        // palabra "password"; en producción se usa `php artisan zuprahost:admin`.
        if (app()->isProduction() && ! app()->runningUnitTests()) {
            $this->command?->error(
                'DatabaseSeeder crea datos de demostración y no debe ejecutarse en producción. '.
                'Usa: php artisan db:seed --class=RolePermissionSeeder  y luego  php artisan zuprahost:admin'
            );

            return;
        }

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

        // El catálogo es lo que vendemos, no datos de demostración: se crea
        // siempre, aunque el cliente de ejemplo ya tenga servicios.
        $this->seedCatalog();
        $this->seedPaymentMethods();

        $this->seedDemoContent($client);
    }

    /**
     * Datos de ejemplo para que el panel del cliente muestre contenido real.
     */
    /**
     * Catálogo de lo que vendemos, con las cuotas que distinguen un plan de
     * otro. Se crea siempre (no depende del cliente de demostración) para que
     * el panel arranque con productos reales que cotizar.
     */
    protected function seedCatalog(): void
    {
        $catalog = [
            ['Hosting Inicia', 'hosting', 'anual', 90, 30, [
                'email_accounts_limit' => 1, 'disk_limit_mb' => 5120, 'bandwidth_limit_mb' => 102400,
                'websites_limit' => 1, 'databases_limit' => 1, 'panel_type' => 'directadmin',
                'specs' => [['label' => 'Certificado SSL', 'value' => 'Incluido']],
            ]],
            ['Hosting Emprende', 'hosting', 'anual', 180, 60, [
                'email_accounts_limit' => 5, 'disk_limit_mb' => 20480, 'bandwidth_limit_mb' => 512000,
                'websites_limit' => 10, 'databases_limit' => 10, 'panel_type' => 'directadmin',
                'specs' => [
                    ['label' => 'Certificado SSL', 'value' => 'Incluido'],
                    ['label' => 'Backups', 'value' => 'Diarios'],
                ],
            ]],
            ['Hosting Empresa', 'hosting', 'anual', 420, 140, [
                'email_accounts_limit' => 0, 'disk_limit_mb' => 61440, 'bandwidth_limit_mb' => 0,
                'websites_limit' => 0, 'databases_limit' => 0, 'panel_type' => 'cpanel',
                'specs' => [
                    ['label' => 'Certificado SSL', 'value' => 'Incluido'],
                    ['label' => 'Backups', 'value' => 'Diarios'],
                    ['label' => 'Soporte prioritario', 'value' => '✓'],
                ],
            ]],
            ['VPS Cloud 4GB', 'vps', 'mensual', 210, 95, [
                'disk_limit_mb' => 81920, 'bandwidth_limit_mb' => 0, 'panel_type' => 'vps',
                'specs' => [
                    ['label' => 'vCPU', 'value' => '2 núcleos'],
                    ['label' => 'Memoria RAM', 'value' => '4 GB'],
                    ['label' => 'Acceso root', 'value' => '✓'],
                ],
            ]],
            ['Correo corporativo', 'correo', 'anual', 120, 45, [
                'email_accounts_limit' => 5, 'panel_type' => 'webmail',
                'specs' => [['label' => 'Espacio por buzón', 'value' => '10 GB']],
            ]],
            ['Dominio .com', 'dominio', 'anual', 60, 35, []],
            ['Dominio .pe', 'dominio', 'anual', 110, 75, []],
            ['Mantenimiento WordPress', 'mantenimiento', 'mensual', 150, 40, [
                'specs' => [
                    ['label' => 'Actualizaciones', 'value' => 'Mensuales'],
                    ['label' => 'Backups verificados', 'value' => '✓'],
                    ['label' => 'Horas de soporte', 'value' => '2 al mes'],
                ],
            ]],
            ['Certificado SSL', 'ssl', 'anual', 90, 30, []],
            ['Licencia Elementor Pro', 'licencia', 'anual', 190, 130, [
                'specs' => [['label' => 'Activaciones', 'value' => '1 sitio']],
            ]],
        ];

        foreach ($catalog as [$name, $category, $cycle, $price, $cost, $extra]) {
            \App\Models\Product::firstOrCreate(
                ['name' => $name],
                array_merge([
                    'category' => $category,
                    'billing_cycle' => $cycle,
                    'price' => $price,
                    'cost' => $cost,
                ], $extra),
            );
        }
    }

    /**
     * Formas de pago que se le ofrecen al cliente. Los datos de cuenta son de
     * ejemplo: se editan desde Finanzas → Métodos de pago.
     */
    protected function seedPaymentMethods(): void
    {
        $methods = [
            ['transferencia', 'Transferencia bancaria', 'manual', 1, [
                'bank' => 'BCP', 'account_holder' => 'MachaData E.I.R.L.',
                'account_number' => '191-1234567-0-89', 'cci' => '00219100123456708955',
                'instructions' => 'Transfiere el monto exacto y sube la constancia. Validamos en menos de 24 horas.',
            ]],
            ['yape', 'Yape', 'manual', 2, [
                'phone' => '987 654 321', 'account_holder' => 'MachaData',
                'instructions' => 'Yapea al número y sube la captura de la operación.',
            ]],
            ['plin', 'Plin', 'manual', 3, [
                'phone' => '987 654 321', 'account_holder' => 'MachaData',
                'instructions' => 'Envía por Plin y sube la captura de la operación.',
            ]],
            ['izipay', 'Izipay', 'enlace', 4, [
                'requires_receipt' => false,
                'instructions' => 'Paga con tarjeta desde el enlace de tu servicio. La confirmación es inmediata.',
            ]],
            ['culqui', 'Culqi', 'enlace', 5, [
                'requires_receipt' => false,
                'instructions' => 'Paga con tarjeta de crédito o débito.',
            ]],
            ['paypal', 'PayPal', 'enlace', 6, [
                'requires_receipt' => false,
                'instructions' => 'Para pagos desde el exterior.',
            ]],
        ];

        foreach ($methods as [$code, $name, $kind, $sort, $extra]) {
            \App\Models\PaymentMethod::firstOrCreate(
                ['code' => $code],
                array_merge(['name' => $name, 'kind' => $kind, 'sort' => $sort], $extra),
            );
        }
    }

    protected function seedDemoContent(Client $client): void
    {
        if ($client->services()->exists()) {
            return;
        }

        $hostingProduct = \App\Models\Product::where('name', 'Hosting Emprende')->first();

        $domain = $client->domains()->create([
            'name' => 'miempresa.com',
            'provider' => 'zupraHost',
            'origin' => 'propio',
            'renewal_managed' => true,
            'registered_at' => now()->subMonths(10),
            'expires_at' => now()->addDays(20),
            'renewal_price' => 60,
            'status' => 'por_vencer',
        ]);

        // Un dominio traído de fuera: el cliente lo renueva por su cuenta, así
        // que no entra en el centro de renovaciones ni se le cobra.
        $client->domains()->create([
            'name' => 'tiendamiempresa.pe',
            'provider' => 'GoDaddy',
            'origin' => 'externo',
            'renewal_managed' => false,
            'registered_at' => now()->subMonths(4),
            'expires_at' => now()->addMonths(8),
            'renewal_price' => 0,
            'status' => 'activo',
        ]);

        $hostingService = $client->services()->create([
            'product_id' => $hostingProduct->id,
            'domain_id' => $domain->id,
            'name' => 'Hosting Emprende · miempresa.com',
            'price' => 180,
            'billing_cycle' => 'anual',
            'starts_at' => now()->subMonths(10),
            'ends_at' => now()->addDays(20),
            'next_renewal_at' => now()->addDays(20),
            'status' => 'activo',
            // El cliente puede pedir factura por lo que pague de este servicio.
            'allows_invoice_request' => true,
            // Enlace de pasarela propio de este servicio.
            'payment_link' => 'https://pago.izipay.pe/demo/hosting-emprende',
        ]);

        // Accesos que ve el cliente en el detalle del servicio.
        $hostingService->hosting()->create([
            'client_id' => $client->id,
            'domain_id' => $domain->id,
            'plan' => 'Emprende',
            'server' => 'srv-lima-01.zuprahost.com',
            'server_ip' => '191.98.144.20',
            'directadmin_user' => 'miempresa',
            'disk_space' => '20 GB SSD',
            'bandwidth' => '500 GB/mes',
            'email_accounts' => '25',
            'databases_limit' => '10',
            'expires_at' => now()->addDays(20),
            'status' => 'activo',
            // Consumo medido: el disco casi lleno para que se vea el aviso
            // de ampliación en el detalle del servicio.
            'disk_used_mb' => 18_640,
            'disk_limit_mb' => 20_480,
            'bandwidth_used_mb' => 143_360,
            'bandwidth_limit_mb' => 512_000,
            'websites_used' => 2,
            'websites_limit' => 10,
            'email_accounts_used' => 4,
            'email_accounts_limit' => 5,
            'databases_used' => 3,
            'databases_limit_count' => 10,
            'datacenter' => 'Lima, Perú',
            'usage_updated_at' => now()->subHours(6),
        ]);

        // Enlaces con los que el cliente entra a su servicio.
        $hostingService->accesses()->createMany([
            [
                'type' => 'directadmin',
                'url' => 'https://srv-lima-01.zuprahost.com:2222',
                'username' => 'miempresa',
                'instructions' => 'Desde aquí administras archivos, correos y bases de datos.',
                'sort' => 1,
            ],
            [
                'type' => 'webmail',
                'url' => 'https://webmail.miempresa.com',
                'username' => 'hola@miempresa.com',
                'instructions' => 'Revisa tu correo desde el navegador, sin configurar nada.',
                'sort' => 2,
            ],
            [
                'type' => 'wordpress',
                'label' => 'Administrador de WordPress',
                'url' => 'https://miempresa.com/wp-admin',
                'sort' => 3,
            ],
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
            'service_id' => $hostingService->id,
            'description' => 'Renovación Hosting Emprende · miempresa.com',
            'quantity' => 1,
            'unit_price' => 180,
        ]);
        $invoice->recalculateTotals();

        // Factura del año pasado ya pagada y validada: así el portal muestra
        // el caso "Pagado" con su comprobante aplicado.
        $paidInvoice = \App\Models\Invoice::create([
            'client_id' => $client->id,
            'code' => \App\Models\Invoice::generateCode(),
            'issued_at' => now()->subMonths(10),
            'due_at' => now()->subMonths(10)->addDays(7),
            'status' => 'pagado',
        ]);
        $paidInvoice->items()->create([
            'service_id' => $hostingService->id,
            'description' => 'Hosting Emprende · miempresa.com (año anterior)',
            'quantity' => 1,
            'unit_price' => 180,
        ]);
        $paidInvoice->recalculateTotals();
        $paidInvoice->payments()->create([
            'client_id' => $client->id,
            'amount' => $paidInvoice->total,
            'method' => 'transferencia',
            'paid_at' => now()->subMonths(10)->addDays(3),
            'operation_code' => 'OP-20250712',
            'receipt_path' => 'receipts/demo.jpg',
            'status' => 'aprobado',
        ]);
    }
}
