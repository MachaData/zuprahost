<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Support\Credentials;
use App\Support\MailDelivery;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ClientAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    protected function client(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'type' => 'natural',
            'name' => 'Rosa Quispe',
            'email' => 'rosa@ejemplo.com',
            'document_type' => 'dni',
            'document_number' => '45678912',
            'status' => 'activo',
            'country' => 'Perú',
        ], $attributes));
    }

    public function test_the_generated_password_satisfies_the_policy(): void
    {
        // Se genera cien veces porque el fallo sería intermitente: una
        // contraseña sin mayúsculas se rechazaría delante del cliente.
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue(Credentials::satisfiesPolicy(Credentials::password()));
        }
    }

    public function test_the_admin_grants_portal_access_and_the_client_can_log_in(): void
    {
        $this->actingAs($this->admin);
        $client = $this->client();

        $password = Credentials::password();

        Livewire::test(ClientResource\Pages\ListClients::class)
            ->callTableAction('crear_acceso', $client, [
                'name' => 'Rosa Quispe',
                'email' => 'rosa@ejemplo.com',
                'password' => $password,
            ]);

        $user = $client->fresh()->user;
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Cliente'));
        $this->assertTrue(Hash::check($password, $user->password));

        // Lo que de verdad importa: que entre al portal y no reciba un 403.
        $this->assertTrue($user->canAccessPanel(\Filament\Facades\Filament::getPanel('client')));
        $this->actingAs($user)->get('/panel')->assertOk();
    }

    public function test_the_admin_resets_a_forgotten_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Vieja-Clave#2026')]);
        $user->assignRole('Cliente');
        $client = $this->client(['user_id' => $user->id]);

        $this->actingAs($this->admin);
        $nueva = Credentials::password();

        Livewire::test(ClientResource\Pages\ListClients::class)
            ->callTableAction('restablecer_acceso', $client, ['password' => $nueva]);

        $user->refresh();
        $this->assertTrue(Hash::check($nueva, $user->password));
        $this->assertFalse(Hash::check('Vieja-Clave#2026', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_each_access_action_shows_only_when_it_applies(): void
    {
        $this->actingAs($this->admin);

        $sinAcceso = $this->client();
        $conAcceso = $this->client([
            'email' => 'otro@ejemplo.com',
            'document_number' => '11223344',
            'user_id' => User::factory()->create()->id,
        ]);

        Livewire::test(ClientResource\Pages\ListClients::class)
            ->assertTableActionVisible('crear_acceso', $sinAcceso)
            ->assertTableActionHidden('restablecer_acceso', $sinAcceso)
            ->assertTableActionHidden('crear_acceso', $conAcceso)
            ->assertTableActionVisible('restablecer_acceso', $conAcceso);
    }

    public function test_the_client_changes_their_own_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Temporal-Clave#2026')]);
        $user->assignRole('Cliente');
        $this->client(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'Temporal-Clave#2026',
                'password' => 'Mi-Propia-Clave#2026',
                'passwordConfirmation' => 'Mi-Propia-Clave#2026',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('Mi-Propia-Clave#2026', $user->fresh()->password));
    }

    public function test_the_forgotten_password_link_depends_on_outgoing_mail(): void
    {
        config(['mail.default' => 'log']);
        $this->assertFalse(MailDelivery::isConfigured());

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => null]);
        $this->assertFalse(MailDelivery::isConfigured());

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.gmail.com']);
        $this->assertTrue(MailDelivery::isConfigured());
    }
}
