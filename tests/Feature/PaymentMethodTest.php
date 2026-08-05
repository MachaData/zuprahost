<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentMethodResource;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Formas de pago: catálogo editable, habilitación por cliente y el enlace,
 * que no es un método del catálogo sino un dato de cada servicio.
 */
class PaymentMethodTest extends TestCase
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
            'type' => 'natural', 'name' => 'Cliente Test', 'document_type' => 'dni',
            'email' => $user->email, 'country' => 'Perú', 'status' => 'activo',
        ]);

        return $user;
    }

    protected function method(string $code, string $name, array $extra = []): PaymentMethod
    {
        return PaymentMethod::create(array_merge([
            'code' => $code, 'name' => $name, 'kind' => 'manual', 'is_active' => true,
        ], $extra));
    }

    protected function openInvoiceFor(User $user, float $total = 100): Invoice
    {
        return Invoice::create([
            'client_id' => $user->client->id, 'code' => Invoice::generateCode(),
            'issued_at' => now(), 'due_at' => now()->addDays(7),
            'subtotal' => $total, 'tax' => 0, 'total' => $total, 'status' => 'pendiente',
        ]);
    }

    public function test_without_a_selection_the_client_sees_every_active_method(): void
    {
        $user = $this->clientUser();
        $this->method('yape', 'Yape');
        $this->method('transferencia', 'Transferencia bancaria');
        $this->method('paypal', 'PayPal', ['is_active' => false]);

        $available = $user->client->availablePaymentMethods();

        $this->assertSame(['Transferencia bancaria', 'Yape'], $available->pluck('name')->sort()->values()->all());
        $this->assertFalse($user->client->hasCustomPaymentMethods());
    }

    public function test_enabling_methods_for_a_client_restricts_them_to_those(): void
    {
        $user = $this->clientUser();
        $yape = $this->method('yape', 'Yape');
        $this->method('plin', 'Plin');
        $this->method('paypal', 'PayPal');

        $user->client->paymentMethods()->sync([$yape->id]);

        $this->assertSame(['Yape'], $user->client->fresh()->availablePaymentMethods()->pluck('name')->all());
        $this->assertTrue($user->client->hasCustomPaymentMethods());
    }

    public function test_a_deactivated_method_disappears_even_if_assigned(): void
    {
        $user = $this->clientUser();
        $yape = $this->method('yape', 'Yape');
        $plin = $this->method('plin', 'Plin');
        $user->client->paymentMethods()->sync([$yape->id, $plin->id]);

        $yape->update(['is_active' => false]);

        $this->assertSame(['Plin'], $user->client->fresh()->availablePaymentMethods()->pluck('name')->all());
    }

    public function test_the_client_sees_account_details_and_instructions(): void
    {
        $user = $this->clientUser();
        $this->method('transferencia', 'Transferencia bancaria', [
            'bank' => 'BCP', 'account_holder' => 'MachaData E.I.R.L.',
            'account_number' => '191-1234567-0-89', 'cci' => '00219100123456708955',
            'instructions' => 'Transfiere el monto exacto y sube la constancia.',
        ]);
        $invoice = $this->openInvoiceFor($user);

        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertSee('Cómo pagar')
            ->assertSee('Transferencia bancaria')
            ->assertSee('191-1234567-0-89')
            ->assertSee('00219100123456708955')
            ->assertSee('Transfiere el monto exacto y sube la constancia.');
    }

    public function test_the_link_only_shows_when_the_service_has_one(): void
    {
        $user = $this->clientUser();
        $this->method('yape', 'Yape');

        $withoutLink = $user->client->services()->create([
            'name' => 'Hosting sin enlace', 'price' => 100, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);
        $invoice = $this->openInvoiceFor($user);
        $invoice->items()->create([
            'service_id' => $withoutLink->id, 'description' => 'Hosting', 'quantity' => 1, 'unit_price' => 100,
        ]);

        $this->assertCount(0, $invoice->paymentLinks());
        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertDontSee('Pagar en línea');

        // Con enlace cargado, aparece el botón.
        $withoutLink->update(['payment_link' => 'https://pago.izipay.pe/demo/abc']);

        $this->assertCount(1, $invoice->refresh()->paymentLinks());
        $this->actingAs($user)->get("/panel/pagos/{$invoice->id}")
            ->assertOk()
            ->assertSee('Pagar en línea')
            ->assertSee('https://pago.izipay.pe/demo/abc');
    }

    public function test_links_do_not_repeat_when_a_service_is_billed_twice(): void
    {
        $user = $this->clientUser();
        $service = $user->client->services()->create([
            'name' => 'Hosting', 'price' => 100, 'billing_cycle' => 'anual', 'status' => 'activo',
            'payment_link' => 'https://pago.izipay.pe/demo/abc',
        ]);
        $invoice = $this->openInvoiceFor($user);
        $invoice->items()->createMany([
            ['service_id' => $service->id, 'description' => 'Hosting', 'quantity' => 1, 'unit_price' => 50],
            ['service_id' => $service->id, 'description' => 'Hosting extra', 'quantity' => 1, 'unit_price' => 50],
        ]);

        $this->assertCount(1, $invoice->paymentLinks());
    }

    public function test_a_settled_invoice_does_not_show_how_to_pay(): void
    {
        $user = $this->clientUser();
        $this->method('yape', 'Yape');
        $invoice = $this->openInvoiceFor($user);
        $invoice->payments()->create([
            'client_id' => $user->client->id, 'amount' => 100, 'method' => 'yape',
            'paid_at' => now(), 'receipt_path' => 'receipts/a.jpg', 'status' => 'aprobado',
        ]);

        $this->actingAs($user)->get("/panel/pagos/{$invoice->refresh()->id}")
            ->assertOk()
            ->assertSee('Factura pagada')
            ->assertDontSee('Cómo pagar');
    }

    public function test_admin_creates_a_payment_method(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $this->actingAs($admin);

        Livewire::test(PaymentMethodResource\Pages\CreatePaymentMethod::class)
            ->fillForm([
                'name' => 'Izipay',
                'code' => 'izipay',
                'kind' => 'enlace',
                'requires_receipt' => false,
                'is_active' => true,
                'sort' => 4,
                'instructions' => 'Paga con tarjeta desde el enlace de tu servicio.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $method = PaymentMethod::where('code', 'izipay')->firstOrFail();
        $this->assertSame('enlace', $method->kind);
        $this->assertFalse($method->requires_receipt);
    }

    public function test_the_code_cannot_be_duplicated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');
        $this->actingAs($admin);
        $this->method('yape', 'Yape');

        Livewire::test(PaymentMethodResource\Pages\CreatePaymentMethod::class)
            ->fillForm(['name' => 'Yape otra vez', 'code' => 'yape', 'kind' => 'manual'])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_a_new_method_code_can_be_saved_on_a_payment(): void
    {
        // El enum antiguo no admitía izipay; ahora el catálogo manda.
        $user = $this->clientUser();
        $this->method('izipay', 'Izipay', ['kind' => 'enlace']);
        $invoice = $this->openInvoiceFor($user);

        $invoice->payments()->create([
            'client_id' => $user->client->id, 'amount' => 100, 'method' => 'izipay',
            'paid_at' => now(), 'status' => 'aprobado',
        ]);

        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'method' => 'izipay']);
        $this->assertSame('pagado', $invoice->refresh()->status);
    }

    public function test_sales_cannot_manage_payment_methods(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('Ventas');
        $this->actingAs($sales)->get('/admin/payment-methods')->assertForbidden();

        $billing = User::factory()->create();
        $billing->assignRole('Facturación');
        $this->actingAs($billing)->get('/admin/payment-methods')->assertOk();
    }
}
