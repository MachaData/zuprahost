<?php

namespace Tests\Feature;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\DomainResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\UserResource;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El alta completa desde el panel: usuario, cliente con acceso, dominio,
 * servicio y factura de un periodo marcada como pagada. Se envían los
 * formularios de verdad, no solo se comprueba que la página cargue.
 */
class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        $this->actingAs($this->admin);
    }

    public function test_admin_creates_a_user_with_a_role(): void
    {
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Ana Soporte',
                'email' => 'ana@zuprahost.com',
                'password' => 'Secreto-Fuerte#2026',
                'roles' => [\Spatie\Permission\Models\Role::where('name', 'Soporte')->first()->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'ana@zuprahost.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Soporte'));
        $this->assertTrue(Hash::check('Secreto-Fuerte#2026', $user->password));
    }

    public function test_admin_creates_a_client_and_grants_portal_access(): void
    {
        Livewire::test(ClientResource\Pages\CreateClient::class)
            ->fillForm([
                'type' => 'empresa',
                'status' => 'activo',
                'name' => 'Mi Empresa S.A.C.',
                'document_type' => 'ruc',
                'document_number' => '20512345678',
                'wants_invoice' => true,
                'email' => 'contacto@miempresa.com',
                'country' => 'Perú',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $client = Client::where('email', 'contacto@miempresa.com')->firstOrFail();
        $this->assertNull($client->user_id);

        Livewire::test(ClientResource\Pages\ListClients::class)
            ->callTableAction('crear_acceso', $client, [
                'name' => 'Mi Empresa S.A.C.',
                'email' => 'portal@miempresa.com',
                'password' => 'Secreto-Fuerte#2026',
            ])
            ->assertHasNoTableActionErrors();

        $client->refresh();
        $this->assertNotNull($client->user_id);
        $this->assertTrue($client->user->hasRole('Cliente'));

        // Y entra de verdad al portal, que es lo que importa.
        $this->actingAs($client->user)->get('/panel')->assertOk();
    }

    public function test_admin_registers_an_external_domain(): void
    {
        $client = $this->client();

        Livewire::test(DomainResource\Pages\CreateDomain::class)
            ->fillForm([
                'client_id' => $client->id,
                'name' => 'traido.pe',
                'status' => 'activo',
                'origin' => 'externo',
                'renewal_managed' => false,
                'provider' => 'GoDaddy',
                'expires_at' => now()->addMonths(6)->toDateString(),
                'renewal_price' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $domain = Domain::where('name', 'traido.pe')->firstOrFail();
        $this->assertTrue($domain->isExternal());
        $this->assertFalse($domain->renewal_managed);
        $this->assertSame('Externo · lo renueva el cliente', $domain->originLabel());
    }

    public function test_admin_contracts_a_service_from_a_plan(): void
    {
        $client = $this->client();
        $plan = Product::create([
            'name' => 'Hosting Emprende', 'category' => 'hosting',
            'billing_cycle' => 'anual', 'price' => 180,
            'email_accounts_limit' => 5, 'disk_limit_mb' => 20480,
        ]);

        Livewire::test(ServiceResource\Pages\CreateService::class)
            ->fillForm([
                'client_id' => $client->id,
                'product_id' => $plan->id,
                'name' => 'Hosting Emprende · miempresa.com',
                'price' => 180,
                'billing_cycle' => 'anual',
                'status' => 'activo',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'allows_invoice_request' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $service = Service::where('client_id', $client->id)->firstOrFail();
        $this->assertTrue($service->allows_invoice_request);
        $this->assertSame($plan->id, $service->product_id);
    }

    public function test_admin_issues_an_invoice_for_a_period_and_marks_it_paid(): void
    {
        $client = $this->client();

        Livewire::test(InvoiceResource\Pages\CreateInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
                'code' => 'INV-2026-09001',
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'period_start' => '2026-09-01',
                'period_end' => '2026-09-30',
                'status' => 'pendiente',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::where('code', 'INV-2026-09001')->firstOrFail();
        $this->assertSame('Septiembre 2026', $invoice->periodLabel());

        // El total sale de los ítems, no se teclea.
        $invoice->items()->create(['description' => 'Hosting septiembre', 'quantity' => 1, 'unit_price' => 180]);
        $invoice->recalculateTotals();
        $this->assertSame(212.40, (float) $invoice->refresh()->total);

        // Registrar el cobro la deja pagada y deja rastro en pagos.
        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->callTableAction('registrar_pago', $invoice, [
                'amount' => 212.40,
                'method' => 'transferencia',
                'paid_at' => now()->toDateString(),
                'operation_code' => 'OP-555',
            ])
            ->assertHasNoTableActionErrors();

        $invoice->refresh();
        $this->assertSame('pagado', $invoice->status);
        $this->assertSame(0.0, $invoice->balance());
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'status' => 'aprobado']);
    }

    public function test_admin_bills_the_whole_period_from_the_invoices_screen(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);
        $client->services()->create([
            'name' => 'Correo', 'price' => 120, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(12),
        ]);

        Livewire::test(InvoiceResource\Pages\ListInvoices::class)
            ->callAction('facturar_periodo', ['days' => 15])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Invoice::count());
        $this->assertSame(354.0, (float) Invoice::first()->total);
    }

    public function test_each_role_can_only_reach_what_it_should(): void
    {
        // Lo que declara RolePermissionSeeder, comprobado de verdad en el panel.
        $expected = [
            'Soporte' => [
                'ok' => ['/admin/clients', '/admin/services', '/admin/tickets'],
                'no' => ['/admin/payments', '/admin/invoices', '/admin/users', '/admin/settings', '/admin/account-statement'],
            ],
            'Ventas' => [
                'ok' => ['/admin/clients', '/admin/services', '/admin/invoices'],
                'no' => ['/admin/payments', '/admin/users', '/admin/settings', '/admin/account-statement'],
            ],
            'Facturación' => [
                'ok' => ['/admin/invoices', '/admin/payments', '/admin/account-statement'],
                'no' => ['/admin/users', '/admin/settings', '/admin/products'],
            ],
        ];

        foreach ($expected as $role => $urls) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user);

            foreach ($urls['ok'] as $url) {
                $this->get($url)->assertOk("{$role} debería entrar a {$url}");
            }
            foreach ($urls['no'] as $url) {
                $this->get($url)->assertForbidden("{$role} no debería entrar a {$url}");
            }
        }
    }

    public function test_sales_cannot_register_payments_but_billing_can(): void
    {
        $client = $this->client();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now()->addDays(7),
            'subtotal' => 100, 'tax' => 0, 'total' => 100, 'status' => 'pendiente',
        ]);

        $sales = User::factory()->create();
        $sales->assignRole('Ventas');
        Livewire::actingAs($sales)
            ->test(InvoiceResource\Pages\ListInvoices::class)
            ->assertTableActionHidden('registrar_pago', $invoice);

        $billing = User::factory()->create();
        $billing->assignRole('Facturación');
        Livewire::actingAs($billing)
            ->test(InvoiceResource\Pages\ListInvoices::class)
            ->assertTableActionVisible('registrar_pago', $invoice);
    }

    protected function client(): Client
    {
        return Client::create([
            'type' => 'natural', 'name' => 'Cliente Test', 'document_type' => 'dni',
            'document_number' => '12345678', 'email' => 'demo@example.com',
            'country' => 'Perú', 'status' => 'activo',
        ]);
    }
}
