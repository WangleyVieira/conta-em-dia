<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaSalario;
use App\Models\Lancamento;
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

        $dados = app(DashboardService::class)->getData();

        $this->assertSame(110.0, $dados['resumo']['despesas']);
        $this->assertSame(85.0, $dados['resumo']['pendente']);
        $this->assertSame(2, $dados['totalLancamentosCategorias']);
        $this->assertSame(40.0, $dados['categorias']['Moradia']);
        $this->assertSame(70.0, $dados['categorias']['Transporte']);
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

        $dados = app(DashboardService::class)->getData();

        $this->assertSame(75.0, $dados['resumo']['pendente']);
        $this->assertSame(25.0, $dados['resumo']['despesas']);
        $this->assertSame(475.0, $dados['resumo']['saldo']);
        $this->assertSame(2000.0, $dados['resumo']['salario']);
        $this->assertSame(25.0, $dados['categorias']['Moradia']);
        $this->assertSame(1, $dados['totalLancamentosCategorias']);
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
