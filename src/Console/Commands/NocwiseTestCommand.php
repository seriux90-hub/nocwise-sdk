<?php

namespace Nocwise\LaravelClient\Console\Commands;

use Illuminate\Console\Command;
use Nocwise\LaravelClient\NocwiseClient;

class NocwiseTestCommand extends Command
{
    protected $signature = 'nocwise:test';

    protected $description = 'Envía un evento de prueba a Nocwise para verificar la configuración';

    public function handle(NocwiseClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('Nocwise no está configurado: revisa NOCWISE_ENABLED, NOCWISE_KEY y NOCWISE_URL en tu .env.');

            return self::FAILURE;
        }

        $error = $client->sendTestEvent('Evento de prueba desde nocwise:test', [
            'source' => 'nocwise:test',
            'app' => config('app.name'),
        ]);

        if ($error !== null) {
            $this->error("No se pudo enviar el evento de prueba: {$error}");

            return self::FAILURE;
        }

        $this->info('Evento de prueba aceptado por Nocwise. Compruébalo en tu proyecto (puede tardar unos segundos).');

        return self::SUCCESS;
    }
}
