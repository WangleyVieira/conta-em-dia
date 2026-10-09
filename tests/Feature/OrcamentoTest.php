<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrcamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_orcamento_e_compartilhado_e_dispara_alerta_aos_oitenta_por_cento(): void
    {
        $categoria = Categoria::create(['descricao' => 'Moradia']);
        $usuario = $this->criarUsuario('orcamento@example.com');
        $this->actingAs($usuario);

        Lancamento::create([
            'tipo' => 'gasto',
            'competencia' => '10/2026',
            'descricao' => 'Aluguel',
            'valor' => 850,
            'valor_pago' => 0,
            'categoria_id' => $categoria->id,
        ]);
        Lancamento::create([
            'tipo' => 'gasto',
            'competencia' => '09/2026',
            'descricao' => 'Despesa do mês passado',
            'valor' => 500,
            'valor_pago' => 0,
            'categoria_id' => $categoria->id,
        ]);

        $this->post(route('orcamento.store'), [
            'competencia' => '10/2026',
            'categoria_id' => $categoria->id,
            'valor_limite' => '1000.00',
        ])->assertRedirect(route('orcamento.index', ['mes' => '2026-10']));

        $this->actingAs($this->criarUsuario('outro-usuario@example.com'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Próximo do limite')
            ->assertSee('85,0% utilizado');

        $this->post(route('orcamento.store'), [
            'competencia' => '10/2026',
            'categoria_id' => $categoria->id,
            'valor_limite' => '1200.00',
        ])->assertRedirect(route('orcamento.index', ['mes' => '2026-10']));

        $this->assertDatabaseCount('orcamentos', 1);
        $this->assertDatabaseHas('orcamentos', [
            'categoria_id' => $categoria->id,
            'competencia' => '10/2026',
            'valor_limite' => 1200,
        ]);
    }

    public function test_orcamento_mostra_limite_ultrapassado_e_pode_ser_removido(): void
    {
        $categoria = Categoria::create(['descricao' => 'Transporte']);
        $usuario = $this->criarUsuario('orcamento-remover@example.com');
        $this->actingAs($usuario);

        $orcamento = Orcamento::create([
            'categoria_id' => $categoria->id,
            'competencia' => '10/2026',
            'valor_limite' => 100,
        ]);
        Lancamento::create([
            'tipo' => 'despesa',
            'competencia' => '10/2026',
            'descricao' => 'Combustível',
            'valor' => 125,
            'valor_pago' => 125,
            'categoria_id' => $categoria->id,
        ]);

        $this->get(route('orcamento.index', ['mes' => '2026-10']))
            ->assertOk()
            ->assertSee('Limite ultrapassado')
            ->assertSee('125,0%');

        $this->delete(route('orcamento.destroy', $orcamento))
            ->assertRedirect(route('orcamento.index', ['mes' => '2026-10']));

        $this->assertDatabaseMissing('orcamentos', ['id' => $orcamento->id]);
    }

    public function test_orcamento_fica_utilizado_em_cem_por_cento_e_ultrapassado_acima_disso(): void
    {
        $categoria = Categoria::create(['descricao' => 'Mercado']);
        $usuario = $this->criarUsuario('orcamento-limite@example.com');
        $this->actingAs($usuario);

        Orcamento::create([
            'categoria_id' => $categoria->id,
            'competencia' => '10/2026',
            'valor_limite' => 100,
        ]);
        $lancamento = Lancamento::create([
            'tipo' => 'gasto',
            'competencia' => '10/2026',
            'descricao' => 'Compras',
            'valor' => 100,
            'valor_pago' => 100,
            'categoria_id' => $categoria->id,
        ]);

        $this->get(route('orcamento.index', ['mes' => '2026-10']))
            ->assertOk()
            ->assertSee('Limite utilizado')
            ->assertDontSee('Limite ultrapassado');

        $lancamento->update(['valor' => 99]);

        $this->get(route('orcamento.index', ['mes' => '2026-10']))
            ->assertOk()
            ->assertSee('Próximo do limite')
            ->assertDontSee('Limite ultrapassado');

        $lancamento->update(['valor' => 100.01]);

        $this->get(route('orcamento.index', ['mes' => '2026-10']))
            ->assertOk()
            ->assertSee('Limite ultrapassado');
    }

    public function test_orcamento_exige_categoria_e_valor_valido(): void
    {
        $usuario = $this->criarUsuario('orcamento-validacao@example.com');

        $this->actingAs($usuario)
            ->from(route('orcamento.index'))
            ->post(route('orcamento.store'), [
                'competencia' => '10/2026',
                'categoria_id' => 999,
                'valor_limite' => 0,
            ])
            ->assertSessionHasErrors(['categoria_id', 'valor_limite']);
    }

    public function test_orcamento_aceita_limite_formatado_com_mascara_brasileira(): void
    {
        $categoria = Categoria::create(['descricao' => 'Alimentação']);
        $usuario = $this->criarUsuario('orcamento-mascara@example.com');

        $this->actingAs($usuario)
            ->post(route('orcamento.store'), [
                'competencia' => '10/2026',
                'categoria_id' => $categoria->id,
                'valor_limite' => '1.234,56',
            ])
            ->assertRedirect(route('orcamento.index', ['mes' => '2026-10']));

        $this->assertDatabaseHas('orcamentos', [
            'categoria_id' => $categoria->id,
            'competencia' => '10/2026',
            'valor_limite' => 1234.56,
        ]);
    }

    private function criarUsuario(string $email): User
    {
        return User::create([
            'name' => 'Pessoa Usuária',
            'email' => $email,
            'password' => 'senha-segura',
        ]);
    }
}
