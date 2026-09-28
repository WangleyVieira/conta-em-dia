<?php

namespace Tests\Unit;

use App\Models\Lancamento;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LancamentoSituacaoTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lancamento_com_vencimento_hoje_permanece_pendente(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');

        $lancamento = new Lancamento([
            'valor' => 100,
            'data_vencimento' => '2026-09-28',
        ]);

        $this->assertSame('pendente', $lancamento->situacao);
    }

    public function test_lancamento_com_vencimento_anterior_a_hoje_fica_vencido(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');

        $lancamento = new Lancamento([
            'valor' => 100,
            'data_vencimento' => '2026-09-27',
        ]);

        $this->assertSame('vencido', $lancamento->situacao);
    }

    #[DataProvider('pagamentosCompletos')]
    public function test_lancamento_pago_fica_com_situacao_paga(array $dados): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');

        $lancamento = new Lancamento(array_merge([
            'valor' => 100,
            'valor_pago' => null,
            'data_vencimento' => '2026-09-01',
        ], $dados));

        $this->assertSame('pago', $lancamento->situacao);
    }

    public static function pagamentosCompletos(): array
    {
        return [
            'marcado como pago' => [['is_pago' => true]],
            'valor pago atingiu o previsto' => [['valor_pago' => 100]],
        ];
    }
}
