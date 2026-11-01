<?php

namespace App\Console\Commands;

use App\Mail\MailTestMailable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MailTest extends Command
{
    protected $signature = 'mail:test {email : Destinatario del correo de prueba} {--sync : Enviar sin usar la cola}';

    protected $description = 'Envía un correo de prueba con la plantilla de Sportiva';

    public function handle(): int
    {
        $email = $this->argument('email');
        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']],
        );

        if ($validator->fails()) {
            $this->error('La dirección de email no es válida.');

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $host = config("mail.mailers.{$mailer}.host")
            ?? ($mailer === 'resend' ? 'api.resend.com' : 'no configurado');
        $fromName = config('mail.from.name');
        $fromAddress = config('mail.from.address');

        $pendingMail = Mail::to($email);
        if ($this->option('sync')) {
            $pendingMail->sendNow(new MailTestMailable);
        } else {
            $pendingMail->send(new MailTestMailable);
        }

        $this->line("Mailer: {$mailer}");
        $this->line("Host: {$host}");
        $this->line("Remitente: {$fromName} <{$fromAddress}>");
        $this->info($this->option('sync')
            ? 'El correo de prueba se envió sin pasar por la cola.'
            : 'El correo de prueba quedó encolado.');

        return self::SUCCESS;
    }
}
