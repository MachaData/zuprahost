<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        return $user;
    }

    protected function clientUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');
        $user->client()->create([
            'type' => 'natural',
            'name' => 'Cliente Test',
            'document_type' => 'dni',
            'email' => $user->email,
            'country' => 'Perú',
            'status' => 'activo',
        ]);

        return $user;
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            '/admin',
            '/admin/clients',
            '/admin/products',
            '/admin/services',
            '/admin/domains',
            '/admin/hostings',
            '/admin/invoices',
            '/admin/payments',
            '/admin/tickets',
            '/admin/licenses',
            '/admin/users',
            '/admin/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_client_pages_render(): void
    {
        $this->actingAs($this->clientUser());

        foreach ([
            '/panel',
            '/panel/servicios',
            '/panel/dominios',
            '/panel/pagos',
            '/panel/licencias',
            '/panel/renovaciones',
            '/panel/dominios?vista=lista',
            '/client/services',
            '/client/domains',
            '/client/invoices',
            '/client/tickets',
            '/client/licenses',
            '/client/billing-profile',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_panel_pages_only_show_own_records(): void
    {
        $mine = $this->clientUser();
        $other = $this->clientUser();

        $other->client->services()->create([
            'name' => 'Servicio ajeno',
            'price' => 100,
            'billing_cycle' => 'anual',
            'status' => 'activo',
        ]);
        $mine->client->services()->create([
            'name' => 'Servicio propio',
            'price' => 100,
            'billing_cycle' => 'anual',
            'status' => 'activo',
        ]);

        $this->actingAs($mine)
            ->get('/panel/servicios')
            ->assertOk()
            ->assertSee('Servicio propio')
            ->assertDontSee('Servicio ajeno');
    }

    public function test_panel_redirects_guests_to_client_login(): void
    {
        foreach (['/panel', '/panel/servicios', '/panel/pagos'] as $url) {
            $this->get($url)->assertRedirect(route('filament.client.auth.login'));
        }
    }

    public function test_payments_list_shows_paid_and_balance(): void
    {
        $user = $this->clientUser();
        $invoice = $this->invoiceFor($user, total: 100.0);

        // Un comprobante aprobado y otro aún en revisión: solo cuenta el primero.
        $invoice->payments()->create([
            'client_id' => $invoice->client_id, 'amount' => 40, 'method' => 'yape',
            'paid_at' => now(), 'receipt_path' => 'receipts/a.jpg', 'status' => 'aprobado',
        ]);
        $invoice->payments()->create([
            'client_id' => $invoice->client_id, 'amount' => 60, 'method' => 'yape',
            'paid_at' => now(), 'receipt_path' => 'receipts/b.jpg', 'status' => 'pendiente',
        ]);

        $this->actingAs($user)->get('/panel/pagos')
            ->assertOk()
            ->assertSee('Mis pagos')
            ->assertSee('S/ 40.00')   // pagado (solo el aprobado)
            ->assertSee('S/ 60.00');  // saldo
    }

    public function test_payment_detail_lists_applied_payments(): void
    {
        $user = $this->clientUser();
        $invoice = $this->invoiceFor($user, total: 100.0);
        $invoice->payments()->create([
            'client_id' => $invoice->client_id, 'amount' => 100, 'method' => 'transferencia',
            'paid_at' => now(), 'operation_code' => 'OP-9988', 'receipt_path' => 'receipts/a.jpg',
            'status' => 'aprobado',
        ]);

        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertSee('Pagos aplicados')
            ->assertSee('OP-9988')
            ->assertSee('Validado')
            ->assertSee('Sin saldo pendiente');
    }

    public function test_service_detail_shows_hosting_access(): void
    {
        $user = $this->clientUser();
        $service = $user->client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);
        $service->hosting()->create([
            'client_id' => $user->client->id, 'plan' => 'Emprende', 'server' => 'srv-lima-01',
            'server_ip' => '191.0.0.20', 'directadmin_user' => 'miempresa', 'status' => 'activo',
        ]);

        $this->actingAs($user)->get("/panel/servicios/{$service->id}")
            ->assertOk()
            ->assertSee('Datos del servidor')
            ->assertSee('srv-lima-01')
            ->assertSee('191.0.0.20')
            ->assertSee('miempresa');
    }

    public function test_detail_pages_reject_other_clients_records(): void
    {
        $mine = $this->clientUser();
        $other = $this->clientUser();

        $invoice = $this->invoiceFor($other, total: 50.0);
        $service = $other->client->services()->create([
            'name' => 'Ajeno', 'price' => 10, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);

        $this->actingAs($mine);
        $this->get("/panel/pagos/{$invoice->id}")->assertNotFound();
        $this->get("/panel/servicios/{$service->id}")->assertNotFound();
    }

    protected function invoiceFor(User $user, float $total): \App\Models\Invoice
    {
        return \App\Models\Invoice::create([
            'client_id' => $user->client->id,
            'code' => \App\Models\Invoice::generateCode(),
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(5),
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'status' => 'pendiente',
        ]);
    }

    public function test_client_dashboard_redirects_to_custom_panel(): void
    {
        $this->actingAs($this->clientUser());
        $this->get('/client')->assertRedirect('/panel');
    }

    public function test_panel_requires_client_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $this->actingAs($admin);
        $this->get('/panel')->assertForbidden();
    }

    public function test_client_cannot_access_admin(): void
    {
        $this->actingAs($this->clientUser());
        $this->get('/admin')->assertForbidden();
    }
}
