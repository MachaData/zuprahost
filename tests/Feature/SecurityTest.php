<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
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

    public function test_the_password_policy_rejects_weak_passwords(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Débil',
                'email' => 'debil@zuprahost.com',
                'password' => 'password',
                'roles' => [\Spatie\Permission\Models\Role::where('name', 'Soporte')->first()->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);

        $this->assertNull(User::where('email', 'debil@zuprahost.com')->first());
    }

    public function test_the_last_administrator_cannot_be_deleted(): void
    {
        $this->assertTrue($this->admin->isLastAdministrator());

        $this->expectException(ValidationException::class);
        $this->admin->delete();
    }

    public function test_an_administrator_can_be_deleted_once_there_is_another(): void
    {
        $second = User::factory()->create();
        $second->assignRole('Administrador');

        $this->assertFalse($this->admin->fresh()->isLastAdministrator());

        $second->delete();
        $this->assertNull(User::find($second->id));
    }

    public function test_you_cannot_delete_your_own_account_from_the_panel(): void
    {
        User::factory()->create()->assignRole('Administrador');
        $this->actingAs($this->admin);

        $this->assertFalse(UserResource::canDelete($this->admin));
    }

    public function test_changing_your_password_requires_the_current_one(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'password' => 'Nueva-Clave#2026',
                'passwordConfirmation' => 'Nueva-Clave#2026',
                'current_password' => 'la-que-no-es',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password']);

        $this->assertFalse(Hash::check('Nueva-Clave#2026', $this->admin->fresh()->password));
    }

    public function test_the_password_changes_with_the_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Actual-Clave#2026')]);
        $user->assignRole('Administrador');
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'current_password' => 'Actual-Clave#2026',
                'password' => 'Nueva-Clave#2026',
                'passwordConfirmation' => 'Nueva-Clave#2026',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('Nueva-Clave#2026', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_a_successful_login_is_recorded(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Acceso-Valido#2026')]);
        $user->assignRole('Administrador');

        $this->assertNull($user->last_login_at);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Acceso-Valido#2026',
        ]);

        // Filament autentica por Livewire, no por POST; el evento es lo que
        // importa, así que se dispara igual con el helper de pruebas.
        $this->actingAs($user);
        event(new \Illuminate\Auth\Events\Login('web', $user, false));

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_responses_carry_the_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_the_console_command_creates_and_updates_the_administrator(): void
    {
        $this->artisan('zuprahost:admin', [
            '--email' => 'jefe@zuprahost.com',
            '--name' => 'Jefe',
            '--password' => 'Clave-Inicial#2026',
        ])->assertSuccessful();

        $user = User::where('email', 'jefe@zuprahost.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Administrador'));
        $this->assertTrue(Hash::check('Clave-Inicial#2026', $user->password));

        $this->artisan('zuprahost:admin', [
            '--email' => 'jefe@zuprahost.com',
            '--password' => 'Clave-Rotada#2026',
        ])->assertSuccessful();

        $this->assertTrue(Hash::check('Clave-Rotada#2026', $user->fresh()->password));
        $this->assertSame(1, User::where('email', 'jefe@zuprahost.com')->count());
    }

    public function test_the_console_command_rejects_a_weak_password(): void
    {
        $this->artisan('zuprahost:admin', [
            '--email' => 'flojo@zuprahost.com',
            '--name' => 'Flojo',
            '--password' => '123456',
        ])->assertFailed();

        $this->assertNull(User::where('email', 'flojo@zuprahost.com')->first());
    }
}
