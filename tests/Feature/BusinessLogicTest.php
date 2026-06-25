<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Services\ApisPeruService;
use App\Services\PaymentManager;
use App\Services\ServiceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function client(): Client
    {
        return Client::create([
            'type' => 'empresa',
            'name' => 'ACME SAC',
            'document_type' => 'ruc',
            'document_number' => '20123456789',
            'email' => 'acme@example.com',
            'country' => 'Perú',
            'status' => 'activo',
        ]);
    }

    public function test_invoice_totals_recalculate_from_items(): void
    {
        $invoice = Invoice::create([
            'client_id' => $this->client()->id,
            'code' => Invoice::generateCode(),
            'status' => 'pendiente',
        ]);

        $invoice->items()->create(['description' => 'Hosting', 'quantity' => 2, 'unit_price' => 50]);
        $invoice->items()->create(['description' => 'Dominio', 'quantity' => 1, 'unit_price' => 40]);
        $invoice->recalculateTotals();

        $this->assertEquals(140.00, (float) $invoice->subtotal);
        $this->assertEquals(25.20, (float) $invoice->tax);
        $this->assertEquals(165.20, (float) $invoice->total);
    }

    public function test_approving_payment_marks_invoice_paid(): void
    {
        $client = $this->client();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'status' => 'pendiente', 'total' => 100,
        ]);
        $payment = Payment::create([
            'client_id' => $client->id, 'invoice_id' => $invoice->id,
            'amount' => 100, 'method' => 'yape', 'status' => 'pendiente',
        ]);

        app(PaymentManager::class)->approve($payment);

        $this->assertEquals('aprobado', $payment->fresh()->status);
        $this->assertEquals('pagado', $invoice->fresh()->status);
    }

    public function test_partial_payment_marks_invoice_partial(): void
    {
        $client = $this->client();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'status' => 'pendiente', 'total' => 200,
        ]);
        $payment = Payment::create([
            'client_id' => $client->id, 'invoice_id' => $invoice->id,
            'amount' => 80, 'method' => 'yape', 'status' => 'pendiente',
        ]);

        app(PaymentManager::class)->approve($payment);

        $this->assertEquals('parcial', $invoice->fresh()->status);
    }

    public function test_service_renewal_extends_and_generates_invoice(): void
    {
        $client = $this->client();
        $service = Service::create([
            'client_id' => $client->id, 'name' => 'Hosting Pro',
            'price' => 120, 'billing_cycle' => 'anual',
            'ends_at' => Carbon::parse('2026-01-01'), 'status' => 'vencido',
        ]);

        $renewal = app(ServiceManager::class)->renew($service, Carbon::parse('2027-01-01'), 120, true);

        $service->refresh();
        $this->assertEquals('activo', $service->status);
        $this->assertEquals('2027-01-01', $service->ends_at->format('Y-m-d'));
        $this->assertNotNull($renewal->invoice_id);
        $this->assertEquals(120.00, (float) $renewal->invoice->subtotal);
    }

    public function test_client_default_document_type_follows_choice(): void
    {
        $noInvoice = Client::create([
            'type' => 'natural', 'name' => 'Sin factura', 'document_type' => 'dni',
            'document_number' => '12345678', 'email' => 'a@b.com', 'country' => 'Perú',
            'wants_invoice' => false,
        ]);
        $this->assertEquals('nota_venta', $noInvoice->defaultDocumentType());

        $boleta = Client::create([
            'type' => 'natural', 'name' => 'Con boleta', 'document_type' => 'dni',
            'document_number' => '87654321', 'email' => 'c@d.com', 'country' => 'Perú',
            'wants_invoice' => true,
        ]);
        $this->assertEquals('boleta', $boleta->defaultDocumentType());

        $factura = Client::create([
            'type' => 'empresa', 'name' => 'Con factura', 'document_type' => 'ruc',
            'document_number' => '20123456789', 'email' => 'e@f.com', 'country' => 'Perú',
            'wants_invoice' => true,
        ]);
        $this->assertEquals('factura', $factura->defaultDocumentType());
    }

    public function test_apisperu_payload_has_required_fields(): void
    {
        $client = $this->client();
        $invoice = Invoice::create([
            'client_id' => $client->id, 'code' => Invoice::generateCode(),
            'document_type' => 'factura', 'series' => 'F001', 'number' => '1',
            'subtotal' => 100, 'tax' => 18, 'total' => 118, 'status' => 'pendiente',
        ]);
        $invoice->items()->create(['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 118]);

        $payload = app(ApisPeruService::class)->buildPayload($invoice->fresh());

        $this->assertEquals('01', $payload['tipoDoc']);
        $this->assertEquals('F001', $payload['serie']);
        $this->assertEquals('6', $payload['client']['tipoDoc']);
        $this->assertEquals('20123456789', $payload['client']['numDoc']);
        $this->assertCount(1, $payload['details']);
    }
}
