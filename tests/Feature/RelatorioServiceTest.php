<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaSalario;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\RelatorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_relatorio_compara_competencia_selecionada_com_mes_anterior(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);

        EntradaSalario::create([
            'competencia' => '08/2026',
            'descricao' => 'Salário',
            'valor_salario' => 1000,
        ]);
        EntradaSalario::create([
            'competencia' => '09/2026',
            'descricao' => 'Salário',
            'valor_salario' => 1100,
        ]);

        $this->criarLancamento($categoria, [
            'competencia' => '08/2026',
            'tipo' => 'receita',
            'descricao' => 'Renda extra',
            'valor' => 200,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '08/2026',
            'valor' => 300,
            'valor_pago' => 100,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '09/2026',
            'tipo' => 'receita',
            'descricao' => 'Venda',
            'valor' => 100,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '09/2026',
            'valor' => 400,
            'valor_pago' => 150,
        ]);
        $this->criarLancamento($categoria, [
            'competencia' => '07/2026',
            'tipo' => 'receita',
            'descricao' => 'Receita de outro mês',
            'valor' => 5000,
        ]);

        $dados = app(RelatorioService::class)->getData('09/2026');

        $this->assertSame(1200.0, $dados['resumo']['receitas']);
        $this->assertSame(400.0, $dados['resumo']['despesas']);
        $this->assertSame(150.0, $dados['resumo']['despesasPagas']);
        $this->assertSame(250.0, $dados['resumo']['despesasPendentes']);
        $this->assertSame(800.0, $dados['resumo']['saldo']);
        $this->assertSame(1200.0, $dados['resumoAnterior']['receitas']);
        $this->assertSame(300.0, $dados['resumoAnterior']['despesas']);
        $this->assertEqualsWithDelta(100.0 / 3, $dados['variacoes']['despesas'], 0.000001);
        $this->assertSame(['atual' => 400.0, 'anterior' => 300.0], $dados['categorias']['Moradia']);
        $this->assertCount(6, $dados['evolucao']);
        $this->assertSame('09/2026', $dados['competencia']);
    }

    public function test_relatorio_mensal_pode_ser_aberto_pelo_usuario_autenticado(): void
    {
        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'pessoa@example.com',
            'password' => 'senha-segura',
        ]);

        $this->actingAs($usuario)
            ->get(route('relatorio.index', ['mes' => '2026-09']))
            ->assertOk()
            ->assertViewIs('relatorio.index')
            ->assertSee('Relatório mensal')
            ->assertSee('Evolução dos últimos seis meses')
            ->assertSee('Despesas por categoria');
    }

    public function test_relatorio_prepara_grafico_com_cinco_maiores_categorias_e_agrupa_demais(): void
    {
        $categorias = collect();
        foreach ([
            'Moradia' => 600,
            'Transporte' => 500,
            'Alimentação' => 400,
            'Saúde' => 300,
            'Lazer' => 200,
            'Educação' => 100,
        ] as $descricao => $valor) {
            $categoria = Categoria::create(['descricao' => $descricao]);
            $categorias->put($descricao, $categoria);
            $this->criarLancamento($categoria, [
                'competencia' => '09/2026',
                'valor' => $valor,
            ]);
            $this->criarLancamento($categoria, [
                'competencia' => '08/2026',
                'valor' => $valor / 2,
            ]);
        }

        $grafico = app(RelatorioService::class)->getData('09/2026')['graficoCategorias'];

        $this->assertSame(
            ['Moradia', 'Transporte', 'Alimentação', 'Saúde', 'Lazer', 'Outras'],
            $grafico['labels']->all()
        );
        $this->assertSame([600.0, 500.0, 400.0, 300.0, 200.0, 100.0], $grafico['atual']->all());
        $this->assertSame([300.0, 250.0, 200.0, 150.0, 100.0, 50.0], $grafico['anterior']->all());
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
