<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaSalario;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\LancamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LancamentoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_da_competencia_subtrai_despesas_do_salario_da_mesma_competencia(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);

        EntradaSalario::create([
            'competencia' => '10/2026',
            'descricao' => 'Salário de outubro',
            'valor_salario' => 1500,
        ]);
        EntradaSalario::create([
            'competencia' => '09/2026',
            'descricao' => 'Salário de setembro',
            'valor_salario' => 9000,
        ]);

        Lancamento::create([
            'tipo' => 'despesa',
            'competencia' => '10/2026',
            'descricao' => 'Aluguel',
            'valor' => 600,
            'valor_pago' => 200,
            'categoria_id' => $categoria->id,
        ]);
        Lancamento::create([
            'tipo' => 'receita',
            'competencia' => '10/2026',
            'descricao' => 'Renda extra',
            'valor' => 500,
            'valor_pago' => 500,
            'categoria_id' => $categoria->id,
        ]);

        $dados = app(LancamentoService::class)->getIndexData(
            Request::create('/lancamentos?competencia=10%2F2026', 'GET')
        );

        $this->assertSame(1500.0, (float) $dados['resumo']['saldo_entrada_salario']);
        $this->assertSame(600.0, (float) $dados['resumo']['despesas']);
        $this->assertSame(900.0, (float) $dados['resumo']['saldo']);
    }

    public function test_pendente_da_competencia_soma_apenas_despesas_na_situacao_pendente(): void
    {
        Carbon::setTestNow('2026-10-09 12:00:00');
        $categoria = Categoria::create(['descricao' => 'Contas']);

        foreach ([
            ['tipo' => 'despesa', 'descricao' => 'Conta futura parcial', 'valor' => 100, 'valor_pago' => 20, 'data_vencimento' => '2026-10-15'],
            ['tipo' => 'gasto', 'descricao' => 'Conta paga', 'valor' => 80, 'valor_pago' => 80, 'data_vencimento' => '2026-10-15'],
            ['tipo' => 'despesa', 'descricao' => 'Conta vencida', 'valor' => 70, 'valor_pago' => 0, 'data_vencimento' => '2026-10-08'],
            ['tipo' => 'receita', 'descricao' => 'Receita futura', 'valor' => 200, 'valor_pago' => 0, 'data_vencimento' => '2026-10-15'],
        ] as $dados) {
            Lancamento::create($dados + [
                'competencia' => '10/2026',
                'categoria_id' => $categoria->id,
            ]);
        }

        $dados = app(LancamentoService::class)->getIndexData(
            Request::create('/lancamentos?competencia=10%2F2026', 'GET')
        );

        $this->assertSame(80.0, (float) $dados['resumo']['pendente']);
    }

    public function test_tela_de_lancamentos_filtra_por_mes_selecionado(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);
        $usuario = User::create([
            'name' => 'Pessoa Usuária',
            'email' => 'lancamentos-filtro@example.com',
            'password' => 'senha-segura',
        ]);

        Lancamento::create([
            'tipo' => 'despesa',
            'competencia' => '09/2026',
            'descricao' => 'Despesa de setembro',
            'valor' => 100,
            'valor_pago' => 0,
            'categoria_id' => $categoria->id,
        ]);
        Lancamento::create([
            'tipo' => 'despesa',
            'competencia' => '10/2026',
            'descricao' => 'Despesa de outubro',
            'valor' => 200,
            'valor_pago' => 0,
            'categoria_id' => $categoria->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('lancamento.index', ['mes' => '2026-09']))
            ->assertOk()
            ->assertSee('Despesa de setembro')
            ->assertDontSee('Despesa de outubro')
            ->assertSee('value="2026-09"', false)
            ->assertSee('R$ 100,00');
    }
}
