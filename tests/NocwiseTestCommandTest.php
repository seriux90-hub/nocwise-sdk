<?php

namespace Nocwise\LaravelClient\Tests;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NocwiseTestCommandTest extends TestCase
{
    public function test_it_succeeds_when_the_server_accepts_the_event(): void
    {
        Http::fake(['nocwise.test/*' => Http::response('', 201)]);

        $this->artisan('nocwise:test')
            ->expectsOutputToContain('Evento de prueba aceptado por Nocwise')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://nocwise.test/api/logs'
            && $request['context']['source'] === 'nocwise:test');
    }

    public function test_it_fails_and_shows_the_reason_when_the_server_rejects_the_event(): void
    {
        Http::fake(['nocwise.test/*' => Http::response('{"message":"Has superado la cuota"}', 402)]);

        $this->artisan('nocwise:test')
            ->expectsOutputToContain('el servidor rechazó el evento (HTTP 402)')
            ->assertFailed();
    }

    public function test_it_fails_and_shows_the_reason_on_a_network_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 60: SSL certificate problem'));

        $this->artisan('nocwise:test')
            ->expectsOutputToContain('fallo de red enviando el evento: cURL error 60')
            ->assertFailed();
    }

    public function test_it_ignores_an_open_circuit_and_sampling(): void
    {
        Http::fake(['nocwise.test/*' => Http::response('', 200)]);
        Cache::put('nocwise:circuit-open', true, 30);
        config(['nocwise.sample_rate' => 0.0]);

        $this->artisan('nocwise:test')->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_it_fails_without_sending_in_dry_run_mode(): void
    {
        Http::fake();
        config(['nocwise.dry_run' => true]);

        $this->artisan('nocwise:test')
            ->expectsOutputToContain('dry-run')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_fails_when_not_configured(): void
    {
        Http::fake();
        config(['nocwise.api_key' => null]);

        $this->artisan('nocwise:test')
            ->expectsOutputToContain('Nocwise no está configurado')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
