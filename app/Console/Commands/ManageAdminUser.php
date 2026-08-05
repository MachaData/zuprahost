<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Crea o actualiza la cuenta de administrador desde la consola.
 *
 * Es la única vía para el primer acceso —y la salida de emergencia si se
 * pierde la contraseña— sin dejar credenciales escritas en un seeder.
 */
class ManageAdminUser extends Command
{
    protected $signature = 'zuprahost:admin
        {--email= : Correo de la cuenta}
        {--name= : Nombre para mostrar}
        {--password= : Contraseña (si se omite, se pide sin mostrarla en pantalla)}';

    protected $description = 'Crea el administrador o cambia su correo y contraseña';

    public function handle(): int
    {
        $email = $this->option('email') ?: text(
            label: 'Correo del administrador',
            required: true,
        );

        $existing = User::query()->where('email', $email)->first();

        $name = $this->option('name')
            ?: $existing?->name
            ?: text(label: 'Nombre para mostrar', default: 'Administrador', required: true);

        $password = $this->option('password') ?: promptPassword(
            label: $existing ? 'Nueva contraseña' : 'Contraseña',
            required: true,
        );

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ],
        );

        // assignRole no duplica si ya lo tiene, así que repetir el comando
        // sobre la misma cuenta solo cambia la contraseña.
        $user->assignRole('Administrador');

        $this->components->info(
            ($user->wasRecentlyCreated ? 'Administrador creado: ' : 'Administrador actualizado: ').$user->email
        );

        $this->components->warn('Entra por /admin con este correo.');

        return self::SUCCESS;
    }
}
