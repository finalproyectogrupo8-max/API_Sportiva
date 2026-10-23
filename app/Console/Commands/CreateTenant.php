<?php

namespace App\Console\Commands;

use App\Actions\Admin\CreateTenantAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateTenant extends Command
{
    protected $signature = 'tenants:create 
                            {slug : Identificador único del club (minúsculas, números y guión bajo)} 
                            {name : Nombre para mostrar del club}';

    protected $description = 'Crea un nuevo club en la plataforma y dispara la provisión de su base de datos';

    public function handle(CreateTenantAction $createTenant): int
    {
        $slug = (string) $this->argument('slug');
        $name = (string) $this->argument('name');

        $validator = Validator::make(
            ['slug' => $slug, 'name' => $name],
            [
                'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:tenants,slug'],
                'name' => ['required', 'string', 'max:255'],
            ],
            [
                'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones bajos (_).',
                'slug.unique' => 'El slug ya está registrado por otro club.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $tenant = $createTenant->execute($name, $slug);

        $this->info("Club [{$tenant->name}] ({$tenant->slug}) creado con base [{$tenant->database_name}].");
        $this->info("Estado actual: [{$tenant->fresh()->status}].");

        return self::SUCCESS;
    }
}
