<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Jobs\Platform\ProvisionTenant;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateTenant extends Command
{
    protected $signature = 'tenants:create 
                            {slug : Identificador único del club (minúsculas, números y guión bajo)} 
                            {name : Nombre para mostrar del club} 
                            {--sync : Ejecuta la provisión de forma síncrona en lugar de encolar el job}';

    protected $description = 'Crea un nuevo club en la base central y encola el job de provisión de su base de datos.';

    public function handle(): int
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

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'database_name' => 'club_'.$slug,
            'status' => TenantStatus::Provisioning->value,
        ]);

        $this->info("Club [{$name}] ({$slug}) registrado con base de datos [{$tenant->database_name}].");

        if ($this->option('sync')) {
            $this->info('Provisionando base de datos sincrónicamente...');
            ProvisionTenant::dispatchSync($tenant->id);
            $this->info("Club activado con éxito (estado: {$tenant->fresh()->status}).");
        } else {
            ProvisionTenant::dispatch($tenant->id);
            $this->info('Job ProvisionTenant despachado a la cola.');
        }

        return self::SUCCESS;
    }
}

