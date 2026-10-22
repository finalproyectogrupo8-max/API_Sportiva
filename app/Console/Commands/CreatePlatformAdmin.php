<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'platform:create-admin {email} {--name=} {--password=}';

    protected $description = 'Crea un usuario administrador de plataforma';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 12 caracteres)');

        if (! $this->option('password') && $password !== $this->secret('Repetir contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $this->argument('email'), 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User($validator->validated());
        $user->is_platform_admin = true;
        $user->save();

        $this->info('Admin de plataforma creado.');

        return self::SUCCESS;
    }
}
