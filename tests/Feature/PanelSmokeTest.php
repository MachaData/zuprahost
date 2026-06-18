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
            '/client',
            '/client/services',
            '/client/domains',
            '/client/invoices',
            '/client/tickets',
            '/client/licenses',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_client_cannot_access_admin(): void
    {
        $this->actingAs($this->clientUser());
        $this->get('/admin')->assertForbidden();
    }
}
