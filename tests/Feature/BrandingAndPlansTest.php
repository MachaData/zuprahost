<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Branding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingAndPlansTest extends TestCase
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

    public function test_branding_falls_back_to_the_defaults(): void
    {
        $this->assertSame(Branding::DEFAULT_NAME, Branding::name());
        $this->assertSame(Branding::DEFAULT_COLOR, Branding::color());
        $this->assertNull(Branding::logoUrl());
    }

    public function test_the_administrator_uploads_a_logo_and_changes_the_name(): void
    {
        Storage::fake('public');

        Livewire::test(Settings::class)
            ->fillForm([
                'brand_name' => 'Mi Hosting',
                'brand_color' => '#ff5500',
                'brand_logo' => [UploadedFile::fake()->image('logo.png', 240, 80)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Mi Hosting', Branding::name());
        $this->assertSame('#ff5500', Branding::color());

        $stored = Setting::get('brand_logo');
        $this->assertNotEmpty($stored);
        Storage::disk('public')->assertExists($stored);
        $this->assertNotNull(Branding::logoUrl());
    }

    public function test_an_invalid_colour_does_not_reach_the_stylesheet(): void
    {
        // El color acaba dentro de un <style> del portal: si se colara texto
        // arbitrario, sería una vía de inyección de CSS.
        Setting::set('brand_color', 'red; } body { display:none', 'marca');

        $this->assertSame(Branding::DEFAULT_COLOR, Branding::color());
    }

    public function test_only_an_administrator_reaches_the_settings_page(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('Ventas');
        $this->actingAs($sales);

        $this->assertFalse(Settings::canAccess());
    }

    public function test_a_plan_is_duplicated_as_an_inactive_copy(): void
    {
        $plan = Product::create([
            'name' => 'Hosting Pro',
            'category' => 'hosting',
            'billing_cycle' => 'anual',
            'price' => 350,
            'cost' => 120,
            'is_active' => true,
            'email_accounts_limit' => 5,
        ]);

        Livewire::test(ProductResource\Pages\ListProducts::class)
            ->callTableAction('replicate', $plan);

        $copy = Product::where('name', 'Hosting Pro (copia)')->firstOrFail();
        $this->assertFalse((bool) $copy->is_active);
        $this->assertSame(5, $copy->email_accounts_limit);
        $this->assertEquals(350, (float) $copy->price);
    }

    public function test_prices_are_adjusted_by_percentage(): void
    {
        $a = Product::create(['name' => 'Plan A', 'category' => 'hosting', 'billing_cycle' => 'mensual', 'price' => 100]);
        $b = Product::create(['name' => 'Plan B', 'category' => 'hosting', 'billing_cycle' => 'mensual', 'price' => 250]);

        Livewire::test(ProductResource\Pages\ListProducts::class)
            ->callTableBulkAction('adjustPrice', [$a, $b], [
                'mode' => 'percent',
                'value' => 10,
                'round' => true,
            ]);

        $this->assertEquals(110, (float) $a->fresh()->price);
        $this->assertEquals(275, (float) $b->fresh()->price);
    }

    public function test_an_adjustment_never_leaves_a_negative_price(): void
    {
        $plan = Product::create(['name' => 'Plan barato', 'category' => 'hosting', 'billing_cycle' => 'mensual', 'price' => 20]);

        Livewire::test(ProductResource\Pages\ListProducts::class)
            ->callTableBulkAction('adjustPrice', [$plan], [
                'mode' => 'amount',
                'value' => -100,
                'round' => true,
            ]);

        $this->assertEquals(0, (float) $plan->fresh()->price);
    }

    public function test_plans_are_deactivated_in_bulk(): void
    {
        $plan = Product::create(['name' => 'Plan viejo', 'category' => 'hosting', 'billing_cycle' => 'mensual', 'price' => 50, 'is_active' => true]);

        Livewire::test(ProductResource\Pages\ListProducts::class)
            ->callTableBulkAction('deactivate', [$plan]);

        $this->assertFalse((bool) $plan->fresh()->is_active);
    }
}
