<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Control de cobranza del administrador: el estado de una factura y la
 * condición de deudor de un cliente siempre se derivan de los pagos
 * validados, nunca de una marca suelta.
 */
class BillingControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function client(string $status = 'activo'): Client
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');

        return $user->client()->create([
            'type' => 'natural',
            'name' => 'Cliente Test',
            'document_type' => 'dni',
            'email' => $user->email,
            'country' => 'Perú',
            'status' => $status,
        ]);
    }

    protected function invoice(Client $client, float $total = 100.0, ?string $dueAt = null): Invoice
    {
        return Invoice::create([
            'client_id' => $client->id,
            'code' => Invoice::generateCode(),
            'issued_at' => now()->subDay(),
            'due_at' => $dueAt ?? now()->addDays(5),
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'status' => 'pendiente',
        ]);
    }

    protected function payment(Invoice $invoice, float $amount, string $status = 'pendiente'): Payment
    {
        return $invoice->payments()->create([
            'client_id' => $invoice->client_id,
            'amount' => $amount,
            'method' => 'transferencia',
            'paid_at' => now(),
            'receipt_path' => 'receipts/x.jpg',
            'status' => $status,
        ]);
    }

    public function test_registering_a_collection_leaves_a_payment_record(): void
    {
        $invoice = $this->invoice($this->client(), 212.40);

        app(PaymentManager::class)->register($invoice, [
            'amount' => 212.40,
            'method' => 'efectivo',
            'paid_at' => today(),
        ]);

        // Lo que ve el cliente y lo que dice la factura tienen que coincidir.
        $this->assertSame('pagado', $invoice->refresh()->status);
        $this->assertSame(212.40, $invoice->amountPaid());
        $this->assertSame(0.0, $invoice->balance());
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'aprobado',
            'method' => 'efectivo',
        ]);
    }

    public function test_partial_collection_leaves_the_invoice_partial(): void
    {
        $invoice = $this->invoice($this->client(), 100.0);

        app(PaymentManager::class)->register($invoice, ['amount' => 40.0]);

        $this->assertSame('parcial', $invoice->refresh()->status);
        $this->assertSame(60.0, $invoice->balance());
    }

    public function test_rejecting_an_approved_payment_reopens_the_invoice(): void
    {
        $invoice = $this->invoice($this->client(), 100.0);
        $payment = $this->payment($invoice, 100.0);
        $manager = app(PaymentManager::class);

        $manager->approve($payment);
        $this->assertSame('pagado', $invoice->refresh()->status);

        $manager->reject($payment, 'El comprobante no corresponde.');

        $this->assertSame('pendiente', $invoice->refresh()->status);
        $this->assertSame(100.0, $invoice->balance());
    }

    public function test_deleting_a_payment_recalculates_the_invoice(): void
    {
        $invoice = $this->invoice($this->client(), 100.0);
        $payment = $this->payment($invoice, 100.0, 'aprobado');

        $this->assertSame('pagado', $invoice->refresh()->status);

        $payment->delete();

        $this->assertSame('pendiente', $invoice->refresh()->status);
    }

    public function test_a_cancelled_invoice_is_never_reopened_by_payments(): void
    {
        $client = $this->client();
        $invoice = $this->invoice($client, 100.0);
        $invoice->update(['status' => 'anulado']);

        $this->payment($invoice, 100.0, 'aprobado');

        $this->assertSame('anulado', $invoice->refresh()->status);
        $this->assertSame(0.0, $invoice->balance());
    }

    public function test_client_becomes_debtor_when_an_invoice_falls_overdue(): void
    {
        $client = $this->client();
        $invoice = $this->invoice($client, 100.0, dueAt: now()->subDays(3)->toDateString());
        $invoice->update(['status' => 'vencido']);

        $client->syncDebtorStatus();
        $this->assertSame('deudor', $client->refresh()->status);

        // Al cobrarla, deja de ser deudor sin que nadie lo cambie a mano.
        app(PaymentManager::class)->register($invoice, ['amount' => 100.0]);

        $this->assertSame('activo', $client->refresh()->status);
    }

    public function test_account_totals_match_the_model_calculations(): void
    {
        $client = $this->client();
        $paid = $this->invoice($client, 100.0);
        $this->payment($paid, 100.0, 'aprobado');

        $open = $this->invoice($client, 250.0);
        $this->payment($open, 50.0, 'aprobado');
        $this->payment($open, 80.0, 'pendiente');  // en revisión: no cuenta

        $row = Client::query()->withAccountTotals()->find($client->id);

        $this->assertSame(350.0, (float) $row->invoiced_sum);
        $this->assertSame(150.0, (float) $row->collected_sum);
        $this->assertSame(200.0, (float) $row->balance_sum);
        $this->assertSame($client->refresh()->outstandingBalance(), (float) $row->balance_sum);
    }

    public function test_admin_can_see_the_account_statement(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $client = $this->client();
        $this->invoice($client, 480.0);

        $this->actingAs($admin)
            ->get('/admin/account-statement')
            ->assertOk()
            ->assertSee('Estado de cuenta')
            ->assertSee('Cliente Test');
    }

    public function test_client_cannot_see_the_account_statement(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');

        $this->actingAs($user)->get('/admin/account-statement')->assertForbidden();
    }
}
