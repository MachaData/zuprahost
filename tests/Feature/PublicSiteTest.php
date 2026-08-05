<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function plan(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Hosting Inicial',
            'category' => 'hosting',
            'billing_cycle' => 'anual',
            'price' => 180,
            'is_active' => true,
            'is_renewable' => true,
        ], $attributes));
    }

    public function test_the_home_page_loads_for_a_visitor(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ver planes y precios')
            ->assertSee('Preguntas frecuentes');
    }

    public function test_it_does_not_break_with_an_empty_catalogue(): void
    {
        // Recién instalado no hay productos. La portada tiene que sostenerse
        // igual: es lo primero que se ve al desplegar.
        $this->assertSame(0, Product::count());

        $this->get('/')
            ->assertOk()
            ->assertSee('Todo lo que tu sitio necesita');
    }

    public function test_active_plans_appear_with_their_price(): void
    {
        $this->plan(['name' => 'Hosting Emprende', 'price' => 199]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Hosting Emprende')
            ->assertSee('S/ 199');
    }

    public function test_an_inactive_plan_stays_off_the_site(): void
    {
        $this->plan(['name' => 'Plan Retirado', 'is_active' => false]);

        $this->get('/')->assertDontSee('Plan Retirado');
    }

    public function test_plan_features_are_listed(): void
    {
        $this->plan([
            'name' => 'Hosting Pro',
            'email_accounts_limit' => 5,
            'websites_limit' => 0,
            'specs' => [['label' => 'Copias diarias', 'value' => 'Incluidas']],
        ]);

        $this->get('/')
            ->assertSee('5 cuentas')
            // 0 significa ilimitado, no "cero sitios".
            ->assertSee('Ilimitado')
            ->assertSee('Copias diarias');
    }

    public function test_domains_are_listed_apart_from_the_plans(): void
    {
        $this->plan(['name' => '.pe', 'category' => 'dominio', 'price' => 120]);

        $response = $this->get('/');

        $response->assertSee('.pe');
        $response->assertSee('Tu nombre en internet');

        // No debe colarse entre las pestañas de planes.
        $this->assertFalse(Catalog::plansByCategory()->has('dominio'));
    }

    public function test_the_page_offers_both_ways_in(): void
    {
        $this->get('/')
            ->assertSee(url('/client/login'))
            ->assertSee(url('/admin'))
            ->assertSee('Área de clientes')
            ->assertSee('Administración');
    }

    public function test_the_services_grid_reflects_what_is_actually_sold(): void
    {
        $this->plan(['name' => 'Hosting Base', 'category' => 'hosting', 'price' => 90]);
        $this->plan(['name' => 'VPS Base', 'category' => 'vps', 'price' => 400]);
        $this->plan(['name' => 'SEO', 'category' => 'seo', 'price' => 500, 'is_active' => false]);

        $keys = collect(Catalog::services())->pluck('key');

        $this->assertTrue($keys->contains('hosting'));
        $this->assertTrue($keys->contains('vps'));
        $this->assertFalse($keys->contains('seo'), 'Una categoría sin productos activos no debe anunciarse.');
    }

    public function test_the_cheapest_price_per_category_is_shown(): void
    {
        $this->plan(['name' => 'Hosting Base', 'price' => 90]);
        $this->plan(['name' => 'Hosting Alto', 'price' => 400]);

        $hosting = collect(Catalog::services())->firstWhere('key', 'hosting');

        $this->assertEquals(90, (float) $hosting['from']);
    }

    public function test_the_branding_reaches_the_public_site(): void
    {
        \App\Models\Setting::set('brand_name', 'Hosting Andino', 'marca');

        $this->get('/')
            ->assertOk()
            ->assertSee('Hosting Andino')
            ->assertSee('Por qué Hosting Andino');
    }
}
