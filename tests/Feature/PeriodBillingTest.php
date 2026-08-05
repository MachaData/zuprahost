<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BillingRunner;
use App\Services\PaymentManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Facturación por periodos y altas del administrador: lo que hace falta para
 * cobrar mes a mes sin escribir cada factura a mano.
 */
class PeriodBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function client(): Client
    {
        return Client::create([
            'type' => 'natural', 'name' => 'Cliente Test', 'document_type' => 'dni',
            'document_number' => '12345678', 'email' => 'demo'.uniqid().'@example.com',
            'country' => 'Perú', 'status' => 'activo',
        ]);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        return $user;
    }

    public function test_a_client_gets_one_invoice_for_all_their_due_services(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);
        $client->services()->create([
            'name' => 'Correo corporativo', 'price' => 120, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(12),
        ]);

        $invoices = app(BillingRunner::class)->run();

        // Una factura con dos líneas, no dos facturas.
        $this->assertCount(1, $invoices);
        $invoice = $invoices->first();
        $this->assertCount(2, $invoice->items);
        $this->assertSame(300.0, (float) $invoice->subtotal);
        $this->assertSame(354.0, (float) $invoice->total);
        $this->assertSame('pendiente', $invoice->status);
    }

    public function test_the_billed_period_starts_after_the_current_one_ends(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);

        $invoice = app(BillingRunner::class)->run()->first();

        // El nuevo tramo arranca el día siguiente al vencimiento vigente.
        $this->assertTrue($invoice->period_start->isSameDay(now()->addDays(11)));
        $this->assertTrue($invoice->period_end->isSameDay(now()->addDays(10)->addYear()));
    }

    public function test_running_twice_does_not_bill_the_same_period_again(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);

        $billing = app(BillingRunner::class);
        $this->assertCount(1, $billing->run());
        $this->assertCount(0, $billing->run());
        $this->assertSame(1, Invoice::count());
    }

    public function test_a_cancelled_invoice_frees_the_period_to_be_billed_again(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);

        $billing = app(BillingRunner::class);
        $billing->run()->first()->update(['status' => 'anulado']);

        $this->assertCount(1, $billing->run());
    }

    public function test_monthly_and_cancelled_services_are_handled_correctly(): void
    {
        $client = $this->client();
        $monthly = $client->services()->create([
            'name' => 'Mantenimiento', 'price' => 150, 'billing_cycle' => 'mensual',
            'status' => 'activo', 'ends_at' => now()->addDays(5),
        ]);
        // Lo cancelado no se cobra, aunque venza.
        $client->services()->create([
            'name' => 'SEO', 'price' => 300, 'billing_cycle' => 'mensual',
            'status' => 'cancelado', 'ends_at' => now()->addDays(5),
        ]);
        // Un servicio sin precio tampoco genera línea.
        $client->services()->create([
            'name' => 'Cortesía', 'price' => 0, 'billing_cycle' => 'mensual',
            'status' => 'activo', 'ends_at' => now()->addDays(5),
        ]);

        $invoice = app(BillingRunner::class)->run()->first();

        $this->assertCount(1, $invoice->items);
        $this->assertSame(150.0, (float) $invoice->subtotal);
        $this->assertSame($monthly->id, $invoice->items->first()->service_id);
        $this->assertTrue($invoice->period_end->isSameDay(now()->addDays(5)->addMonth()));
    }

    public function test_period_label_reads_naturally(): void
    {
        $client = $this->client();

        $sameMonth = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now(),
            'period_start' => '2026-08-01', 'period_end' => '2026-08-31',
            'status' => 'pendiente',
        ]);
        $crossing = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now(),
            'period_start' => '2026-08-01', 'period_end' => '2027-07-31',
            'status' => 'pendiente',
        ]);
        $none = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now(), 'status' => 'pendiente',
        ]);

        $this->assertSame('Agosto 2026', $sameMonth->periodLabel());
        $this->assertSame('01/08/2026 – 31/07/2027', $crossing->periodLabel());
        $this->assertNull($none->periodLabel());
    }

    public function test_adding_an_item_to_a_paid_invoice_reopens_it(): void
    {
        $client = $this->client();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now()->addDays(7), 'status' => 'pendiente',
        ]);
        $invoice->items()->create(['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 100]);
        $invoice->recalculateTotals();

        app(PaymentManager::class)->register($invoice->refresh(), ['amount' => 118]);
        $this->assertSame('pagado', $invoice->refresh()->status);

        // Cambiar el total cambia el saldo: no puede seguir diciendo "pagado".
        $invoice->items()->create(['description' => 'Extra', 'quantity' => 1, 'unit_price' => 200]);
        $invoice->recalculateTotals();

        $invoice->refresh();
        $this->assertSame('parcial', $invoice->status);
        $this->assertSame(236.0, $invoice->balance());
    }

    public function test_admin_creates_portal_access_for_a_client(): void
    {
        $admin = $this->admin();
        $client = $this->client();

        $this->assertNull($client->user_id);

        $this->actingAs($admin)->get('/admin/clients')->assertOk()->assertSee('Dar acceso al portal');

        // Lo que hace la acción: usuario con rol Cliente, vinculado a la ficha.
        $user = User::create([
            'name' => $client->name, 'email' => 'nuevo@example.com',
            'password' => bcrypt('secret-1234'),
        ]);
        $user->assignRole('Cliente');
        $client->update(['user_id' => $user->id]);

        $this->assertTrue($user->hasRole('Cliente'));
        $this->actingAs($user->refresh())->get('/panel')->assertOk();
    }

    public function test_the_command_reports_what_it_would_bill(): void
    {
        $client = $this->client();
        $client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'ends_at' => now()->addDays(10),
        ]);

        $this->artisan('app:generate-period-invoices', ['--dry-run' => true])
            ->expectsOutputToContain('Hosting Emprende')
            ->assertSuccessful();

        $this->assertSame(0, Invoice::count());

        $this->artisan('app:generate-period-invoices')->assertSuccessful();
        $this->assertSame(1, Invoice::count());
    }

    public function test_client_downloads_the_attached_document_when_the_admin_uploads_one(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');
        $client = $user->client()->create([
            'type' => 'natural', 'name' => 'Cliente Test', 'document_type' => 'dni',
            'email' => $user->email, 'country' => 'Perú', 'status' => 'activo',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now(), 'subtotal' => 100, 'tax' => 0,
            'total' => 100, 'status' => 'pagado',
        ]);

        // Sin archivo no hay nada que descargar.
        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/comprobante")->assertNotFound();

        \Illuminate\Support\Facades\Storage::disk('public')->put('invoices/f001-128.pdf', '%PDF-1.4 fake');
        $invoice->update(['attachment_path' => 'invoices/f001-128.pdf', 'attachment_name' => 'Factura F001-000128']);

        $response = $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/comprobante");
        $response->assertOk();
        $this->assertStringContainsString('factura-f001-000128.pdf', $response->headers->get('content-disposition'));

        \Illuminate\Support\Facades\Storage::disk('public')->delete('invoices/f001-128.pdf');
    }
}
