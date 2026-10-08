<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CotacaoDolarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CotacaoDolarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('cotacao.usd-brl');
    }

    protected function tearDown(): void
    {
        Cache::forget('cotacao.usd-brl');
        parent::tearDown();
    }

    public function test_cotacao_nao_fica_disponivel_sem_autenticacao(): void
    {
        $this->get(route('cotacao.dolar'))
            ->assertRedirect(route('login'));
    }

    public function test_salario_minimo_vigente_aparece_ao_lado_da_cotacao(): void
    {
        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'salario-minimo@example.com',
            'password' => 'senha-segura',
        ]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Salário mínimo · 2026')
            ->assertSee('R$ 1.621,00');
    }

    public function test_usuario_autenticado_pode_consultar_a_cotacao_do_dolar(): void
    {
        Http::fake([
            'economia.awesomeapi.com.br/json/last/USD-BRL' => Http::response([
                'USDBRL' => [
                    'bid' => '5.0213',
                    'ask' => '5.0225',
                    'pctChange' => '-0.015929',
                    'timestamp' => '1791465669',
                ],
            ]),
        ]);

        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'cotacao@example.com',
            'password' => 'senha-segura',
        ]);

        $this->actingAs($usuario)
            ->getJson(route('cotacao.dolar'))
            ->assertOk()
            ->assertExactJson([
                'compra' => 5.0213,
                'venda' => 5.0225,
                'variacao' => -0.015929,
                'atualizadoEm' => 1791465669,
            ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://economia.awesomeapi.com.br/json/last/USD-BRL');
    }

    public function test_cotacao_fica_em_cache_e_falha_da_api_e_informada(): void
    {
        Http::fakeSequence()
            ->push(['USDBRL' => [
                'bid' => '5.00',
                'ask' => '5.01',
                'pctChange' => '0.1',
                'timestamp' => '1791465669',
            ]])
            ->push([], 503);

        $servico = app(CotacaoDolarService::class);

        $this->assertSame(5.0, $servico->obter()['compra']);
        $this->assertSame(5.0, $servico->obter()['compra']);
        Http::assertSentCount(1);

        Cache::forget('cotacao.usd-brl');

        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'cotacao-falha@example.com',
            'password' => 'senha-segura',
        ]);

        $this->actingAs($usuario)
            ->getJson(route('cotacao.dolar'))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'A cotação do dólar está temporariamente indisponível.');
    }
}
