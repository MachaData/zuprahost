<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Descarga de comprobantes y solicitud de factura electrónica. Lo que habilita
 * pedir factura es el servicio facturado, no una preferencia global.
 */
class InvoiceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function clientUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');
        $user->client()->create([
            'type' => 'natural',
            'name' => 'Cliente Test',
            'document_type' => 'dni',
            'document_number' => '12345678',
            'email' => $user->email,
            'country' => 'Perú',
            'status' => 'activo',
        ]);

        return $user;
    }

    /**
     * Factura pagada de un servicio, con el permiso de pedir factura a elección.
     */
    protected function paidInvoiceFor(User $user, bool $allowsRequest): Invoice
    {
        $service = $user->client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'allows_invoice_request' => $allowsRequest,
        ]);

        $invoice = Invoice::create([
            'client_id' => $user->client->id,
            'code' => Invoice::generateCode(),
            'issued_at' => now()->subDays(10),
            'due_at' => now()->subDays(3),
            'subtotal' => 180, 'tax' => 32.40, 'total' => 212.40,
            'status' => 'pendiente',
            'document_type' => 'nota_venta',
        ]);
        $invoice->items()->create([
            'service_id' => $service->id,
            'description' => 'Hosting Emprende', 'quantity' => 1, 'unit_price' => 212.40,
        ]);
        $invoice->payments()->create([
            'client_id' => $user->client->id, 'amount' => 212.40, 'method' => 'transferencia',
            'paid_at' => now()->subDays(2), 'operation_code' => 'OP-1234',
            'receipt_path' => 'receipts/a.jpg', 'status' => 'aprobado',
        ]);

        return $invoice->refresh();
    }

    public function test_paid_tab_lists_settled_invoices_only(): void
    {
        $user = $this->clientUser();
        $paid = $this->paidInvoiceFor($user, allowsRequest: true);

        $open = Invoice::create([
            'client_id' => $user->client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now()->addDays(5),
            'subtotal' => 100, 'tax' => 0, 'total' => 100, 'status' => 'pendiente',
        ]);

        $this->actingAs($user)->get('/panel/pagos?estado=pagadas')
            ->assertOk()
            ->assertSee($paid->code)
            ->assertDontSee($open->code);

        $this->actingAs($user)->get('/panel/pagos')
            ->assertOk()
            ->assertSee($open->code)
            ->assertDontSee($paid->code);
    }

    public function test_paid_invoice_offers_receipt_download(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: false);

        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertSee('Factura pagada')
            ->assertSee('Descargar recibo');

        $response = $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/recibo");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            "recibo-{$invoice->code}.pdf",
            $response->headers->get('content-disposition'),
        );
    }

    public function test_request_button_appears_only_when_the_service_allows_it(): void
    {
        $allowed = $this->clientUser();
        $invoice = $this->paidInvoiceFor($allowed, allowsRequest: true);
        $this->actingAs($allowed)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertSee('Solicitar factura');

        $denied = $this->clientUser();
        $other = $this->paidInvoiceFor($denied, allowsRequest: false);
        $this->actingAs($denied)->get("/panel/pagos/{$other->id}")
            ->assertOk()
            ->assertDontSee('Solicitar factura');
    }

    public function test_requesting_with_ruc_turns_the_document_into_a_factura(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: true);

        $this->actingAs($user)
            ->post("/panel/pagos/{$invoice->id}/factura", [
                'billing_document_type' => 'ruc',
                'billing_document_number' => '20512345678',
                'billing_name' => 'Mi Empresa S.A.C.',
                'billing_address' => 'Av. Arequipa 123, Lima',
            ])
            ->assertRedirect(route('panel.payment', $invoice));

        $invoice->refresh();
        $this->assertSame('factura', $invoice->document_type);
        $this->assertSame('20512345678', $invoice->billing_document_number);
        $this->assertNotNull($invoice->invoice_requested_at);

        // Pedida una vez, el botón ya no se ofrece.
        $this->assertFalse($invoice->canRequestElectronicInvoice());
    }

    public function test_requesting_with_dni_produces_a_boleta(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: true);

        $this->actingAs($user)->post("/panel/pagos/{$invoice->id}/factura", [
            'billing_document_type' => 'dni',
            'billing_document_number' => '87654321',
            'billing_name' => 'Juan Pérez',
        ]);

        $this->assertSame('boleta', $invoice->refresh()->document_type);
    }

    public function test_ruc_must_have_eleven_digits(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: true);

        $this->actingAs($user)
            ->post("/panel/pagos/{$invoice->id}/factura", [
                'billing_document_type' => 'ruc',
                'billing_document_number' => '123',
                'billing_name' => 'Mi Empresa S.A.C.',
            ])
            ->assertSessionHasErrors('billing_document_number');

        $this->assertNull($invoice->refresh()->invoice_requested_at);
    }

    public function test_requesting_is_rejected_when_the_service_does_not_allow_it(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: false);

        $this->actingAs($user)->post("/panel/pagos/{$invoice->id}/factura", [
            'billing_document_type' => 'ruc',
            'billing_document_number' => '20512345678',
            'billing_name' => 'Mi Empresa S.A.C.',
        ])->assertForbidden();
    }

    public function test_an_unpaid_invoice_cannot_be_invoiced_or_downloaded(): void
    {
        $user = $this->clientUser();
        $service = $user->client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual',
            'status' => 'activo', 'allows_invoice_request' => true,
        ]);
        $invoice = Invoice::create([
            'client_id' => $user->client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now()->addDays(5),
            'subtotal' => 180, 'tax' => 0, 'total' => 180, 'status' => 'pendiente',
        ]);
        $invoice->items()->create([
            'service_id' => $service->id, 'description' => 'Hosting', 'quantity' => 1, 'unit_price' => 180,
        ]);

        $this->assertFalse($invoice->canRequestElectronicInvoice());
        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/recibo")->assertNotFound();
    }

    public function test_receipts_of_other_clients_are_not_reachable(): void
    {
        $mine = $this->clientUser();
        $other = $this->clientUser();
        $invoice = $this->paidInvoiceFor($other, allowsRequest: true);

        $this->actingAs($mine)->get("/panel/pagos/{$invoice->id}/recibo")->assertNotFound();
        $this->actingAs($mine)->post("/panel/pagos/{$invoice->id}/factura", [
            'billing_document_type' => 'ruc',
            'billing_document_number' => '20512345678',
            'billing_name' => 'Ajena S.A.C.',
        ])->assertNotFound();
    }

    public function test_electronic_document_is_only_served_once_issued(): void
    {
        $user = $this->clientUser();
        $invoice = $this->paidInvoiceFor($user, allowsRequest: true);

        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/comprobante")->assertNotFound();

        $invoice->update(['document_type' => 'factura', 'sunat_status' => 'aceptado', 'series' => 'F001', 'number' => '128']);

        $response = $this->actingAs($user)->get("/panel/pagos/{$invoice->id}/comprobante");
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_admin_toggles_the_permission_per_service(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $user = $this->clientUser();
        $service = $user->client->services()->create([
            'name' => 'Hosting', 'price' => 180, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);

        // El permiso nace apagado: hay que habilitarlo a propósito.
        $this->assertFalse($service->refresh()->allows_invoice_request);

        $this->actingAs($admin)
            ->get("/admin/services/{$service->id}/edit")
            ->assertOk()
            ->assertSee('El cliente puede solicitar factura');

        $service->update(['allows_invoice_request' => true]);
        $this->assertTrue(Service::find($service->id)->allows_invoice_request);
    }
}
