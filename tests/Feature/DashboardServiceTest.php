<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaSalario;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_soma_despesas_pagas_e_pendentes_de_todas_as_competencias(): void
    {
        $categoriaCasa = Categoria::create(['descricao' => 'Moradia']);
        $categoriaTransporte = Categoria::create(['descricao' => 'Transporte']);

        $this->criarLancamento($categoriaCasa, [
            'competencia' => '08/2026',
            'descricao' => 'Aluguel',
            'valor' => 125,
            'valor_pago' => 40,
        ]);

        $this->criarLancamento($categoriaTransporte, [
            'competencia' => '09/2026',
            'descricao' => 'Combustível',
            'valor' => 70,
            'valor_pago' => 70,
        ]);

        $dados = app(DashboardService::class)->getData('09/2026');

        $this->assertSame(70.0, $dados['resumo']['despesas']);
        $this->assertSame(0.0, $dados['resumo']['pendente']);
        $this->assertSame(1, $dados['totalLancamentosCategorias']);
        $this->assertSame(70.0, $dados['categorias']['Transporte']);
        $this->assertSame('09/2026', $dados['competencia']);
    }

    public function test_dashboard_nao_considera_receita_como_despesa_pendente(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);

        $this->criarLancamento($categoria, [
            'tipo' => 'receita',
            'descricao' => 'Renda recebida',
            'valor' => 500,
            'valor_pago' => 0,
        ]);

        $this->criarLancamento($categoria, [
            'tipo' => 'gasto',
            'descricao' => 'Conta de água',
            'valor' => 100,
            'valor_pago' => 25,
        ]);

        EntradaSalario::create([
            'competencia' => '09/2026',
            'descricao' => 'Salário',
            'valor_salario' => 2000,
        ]);

        $dados = app(DashboardService::class)->getData('09/2026');

        $this->assertSame(75.0, $dados['resumo']['pendente']);
        $this->assertSame(25.0, $dados['resumo']['despesas']);
        $this->assertSame(475.0, $dados['resumo']['saldo']);
        $this->assertSame(2000.0, $dados['resumo']['salario']);
        $this->assertSame(25.0, $dados['categorias']['Moradia']);
        $this->assertSame(1, $dados['totalLancamentosCategorias']);
    }

    public function test_dashboard_filtra_resumo_por_competencia_e_disponibiliza_outros_meses(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);
        EntradaSalario::create([
            'competencia' => '09/2026',
            'descricao' => 'Salário setembro',
            'valor_salario' => 1000,
        ]);
        EntradaSalario::create([
            'competencia' => '10/2026',
            'descricao' => 'Salário outubro',
            'valor_salario' => 1200,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '09/2026',
            'valor' => 900,
            'valor_pago' => 900,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '10/2026',
            'valor' => 200,
            'valor_pago' => 50,
        ]);

        $dados = app(DashboardService::class)->getData('10/2026');

        $this->assertSame(50.0, $dados['resumo']['despesas']);
        $this->assertSame(150.0, $dados['resumo']['pendente']);
        $this->assertSame(1200.0, $dados['resumo']['salario']);
        $this->assertSame(1, $dados['totalLancamentosCategorias']);
        $this->assertSame(50.0, $dados['categorias']['Moradia']);
        $this->assertSame(['10/2026', '09/2026'], $dados['competenciasDisponiveis']->all());
        $this->assertSame('10/2026', $dados['lancamentosRecentes']->first()->competencia);
    }

    public function test_dashboard_permite_selecionar_uma_competencia_anterior(): void
    {
        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'dashboard-competencia@example.com',
            'password' => 'senha-segura',
        ]);

        $resposta = $this->actingAs($usuario)
            ->get(route('dashboard', ['mes' => '2026-09']));

        $resposta->assertOk()
            ->assertSee('setembro 2026')
            ->assertSee('name="mes"', false)
            ->assertSee('value="2026-09"', false)
            ->assertSee('Lançamentos de');
    }

    private function criarLancamento(Categoria $categoria, array $dados): Lancamento
    {
        return Lancamento::create(array_merge([
            'tipo' => 'gasto',
            'competencia' => '09/2026',
            'descricao' => 'Despesa de teste',
            'valor' => 100,
            'valor_pago' => 0,
            'categoria_id' => $categoria->id,
        ], $dados));
    }
}
