<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planes, accesos del cliente y origen de los dominios: lo que sostiene
 * vender hosting, correo, VPS, licencias y mantenimiento en el mismo panel.
 */
class CatalogAndAccessTest extends TestCase
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
            'email' => $user->email,
            'country' => 'Perú',
            'status' => 'activo',
        ]);

        return $user;
    }

    public function test_plan_features_list_quotas_and_extras(): void
    {
        $plan = Product::create([
            'name' => 'Hosting Emprende',
            'category' => 'hosting',
            'billing_cycle' => 'anual',
            'price' => 180,
            'email_accounts_limit' => 5,
            'disk_limit_mb' => 20480,
            'websites_limit' => 1,
            'specs' => [['label' => 'Backups', 'value' => 'Diarios']],
        ]);

        $features = collect($plan->planFeatures())->pluck('value', 'label');

        $this->assertSame('5 cuentas', $features['Cuentas de correo']);
        $this->assertSame('20.0 GB', $features['Espacio en disco']);
        $this->assertSame('1 sitio', $features['Sitios web']);
        $this->assertSame('Diarios', $features['Backups']);
        // Sin transferencia definida no se inventa una fila.
        $this->assertArrayNotHasKey('Transferencia', $features);
    }

    public function test_a_zero_quota_means_unlimited(): void
    {
        $plan = Product::create([
            'name' => 'Hosting Empresa',
            'category' => 'hosting',
            'billing_cycle' => 'anual',
            'price' => 420,
            'email_accounts_limit' => 0,
        ]);

        $features = collect($plan->planFeatures())->pluck('value', 'label');

        $this->assertSame('Ilimitado', $features['Cuentas de correo']);
    }

    public function test_client_sees_only_visible_accesses(): void
    {
        $user = $this->clientUser();
        $service = $user->client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);

        $service->accesses()->createMany([
            ['type' => 'directadmin', 'url' => 'https://srv-lima-01.zuprahost.com:2222', 'username' => 'miempresa'],
            ['type' => 'ftp', 'label' => 'FTP interno', 'url' => 'ftp://interno', 'is_visible' => false],
        ]);

        $this->actingAs($user)->get("/panel/servicios/{$service->id}")
            ->assertOk()
            ->assertSee('Entrar a tu servicio')
            ->assertSee('DirectAdmin')
            ->assertSee('srv-lima-01.zuprahost.com:2222')
            ->assertDontSee('FTP interno');
    }

    public function test_service_detail_shows_the_contracted_plan(): void
    {
        $user = $this->clientUser();
        $plan = Product::create([
            'name' => 'Correo corporativo',
            'category' => 'correo',
            'billing_cycle' => 'anual',
            'price' => 120,
            'email_accounts_limit' => 5,
        ]);
        $service = $user->client->services()->create([
            'product_id' => $plan->id,
            'name' => 'Correo corporativo', 'price' => 120, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);

        $this->actingAs($user)->get("/panel/servicios/{$service->id}")
            ->assertOk()
            ->assertSee('Lo que incluye tu plan')
            ->assertSee('5 cuentas');
    }

    public function test_domain_origin_is_explicit_not_guessed(): void
    {
        $client = $this->clientUser()->client;

        $own = $client->domains()->create([
            'name' => 'miempresa.com', 'provider' => 'zupraHost',
            'origin' => 'propio', 'renewal_managed' => true,
        ]);
        $broughtIn = $client->domains()->create([
            'name' => 'otra.pe', 'provider' => 'GoDaddy',
            'origin' => 'externo', 'renewal_managed' => true,
        ]);
        $theirProblem = $client->domains()->create([
            'name' => 'suya.com', 'provider' => 'Namecheap',
            'origin' => 'externo', 'renewal_managed' => false,
        ]);

        $this->assertFalse($own->isExternal());
        $this->assertSame('Registrado con nosotros', $own->originLabel());
        $this->assertSame('Externo · lo renovamos nosotros', $broughtIn->originLabel());
        $this->assertSame('Externo · lo renueva el cliente', $theirProblem->originLabel());
    }

    public function test_renewal_centre_skips_domains_the_client_renews_alone(): void
    {
        $user = $this->clientUser();

        $user->client->domains()->create([
            'name' => 'nuestra.com', 'origin' => 'propio', 'renewal_managed' => true,
            'expires_at' => now()->addDays(15), 'renewal_price' => 60,
        ]);
        $user->client->domains()->create([
            'name' => 'ajena.com', 'origin' => 'externo', 'renewal_managed' => false,
            'expires_at' => now()->addDays(15), 'renewal_price' => 60,
        ]);

        $this->actingAs($user)->get('/panel/renovaciones')
            ->assertOk()
            ->assertSee('nuestra.com')
            ->assertDontSee('ajena.com');
    }

    public function test_admin_manages_accesses_from_the_service(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $client = Client::create([
            'type' => 'natural', 'name' => 'Cliente Test', 'document_type' => 'dni',
            'email' => 'demo@example.com', 'country' => 'Perú', 'status' => 'activo',
        ]);
        $service = $client->services()->create([
            'name' => 'Hosting Emprende', 'price' => 180, 'billing_cycle' => 'anual', 'status' => 'activo',
        ]);

        $this->actingAs($admin)
            ->get("/admin/services/{$service->id}/edit")
            ->assertOk()
            ->assertSee('Accesos del cliente');
    }
}
