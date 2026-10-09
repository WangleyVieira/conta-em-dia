<?php

namespace App\Services;

use App\Models\EntradaSalario;
use App\Models\Lancamento;

class DashboardService
{
    public function __construct(private readonly OrcamentoService $orcamentoService)
    {
    }

    /**
     * Get the data for the dashboard.
     */
    public function getData(): array
    {
        $lancamentos = $this->buscarLancamentos();
        $despesasPagas = $this->calcularDespesasPagas($lancamentos);
        $pendente = $this->calcularValorPendente($lancamentos);
        $orcamentoMensal = $this->orcamentoService->getDashboardData(now()->format('m/Y'));

        return [
            'competenciaAtual' => 'todos os cadastros',
            'resumo' => $this->montarResumo($lancamentos, $despesasPagas, $pendente),
            'lancamentosRecentes' => $lancamentos->take(6),
            'categorias' => $this->calcularTotaisPorCategoria($lancamentos),
            'totalLancamentosCategorias' => $this->contarDespesas($lancamentos),
            'comparativo' => [
                'pago' => $despesasPagas,
                'pendente' => $pendente,
            ],
            'orcamentoMensal' => $orcamentoMensal,
        ];
    }

    /**
     * Fetch all lancamentos with their associated categories, ordered by creation date descending.
     */
    private function buscarLancamentos()
    {
        return Lancamento::with('categoria')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Calculate the total amount of paid expenses.
     */
    private function calcularDespesasPagas($lancamentos): float
    {
        $total = 0;

        foreach ($lancamentos as $lancamento) {
            if ($this->ehDespesa($lancamento)) {
                $total += (float) ($lancamento->valor_pago ?? 0);
            }
        }

        return $total;
    }

    /**
     * Calculate the total pending amount for all lancamentos.
     */
    private function calcularValorPendente($lancamentos): float
    {
        $total = 0;

        foreach ($lancamentos as $lancamento) {
            if (!$this->ehDespesa($lancamento)) {
                continue;
            }

            $valor = (float) $lancamento->valor;
            $valorPago = (float) ($lancamento->valor_pago ?? 0);
            $total += max(0, $valor - $valorPago);
        }

        return $total;
    }

    /**
     * Count only expenses included in the category chart.
     */
    private function contarDespesas($lancamentos): int
    {
        $total = 0;

        foreach ($lancamentos as $lancamento) {
            if ($this->ehDespesa($lancamento)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Calculate the total amounts for each category.
     */
    private function calcularTotaisPorCategoria($lancamentos)
    {
        $totais = [];

        foreach ($lancamentos as $lancamento) {
            if (!$this->ehDespesa($lancamento)) {
                continue;
            }

            $categoria = $lancamento->categoria?->descricao ?? 'Sem categoria';
            $totais[$categoria] = ($totais[$categoria] ?? 0) + (float) ($lancamento->valor_pago ?? 0);
        }

        arsort($totais);

        return collect($totais);
    }

    /**
     * Mount the summary data for the dashboard, including total expenses, pending amounts, balance, salary, and total lancamentos.
     */
    private function montarResumo($lancamentos, float $despesasPagas, float $pendente): array
    {
        $receitas = 0;

        foreach ($lancamentos as $lancamento) {
            if ($lancamento->tipo === 'receita') {
                $receitas += (float) $lancamento->valor;
            }
        }

        return [
            'despesas' => $despesasPagas,
            'pendente' => $pendente,
            'saldo' => $receitas - $despesasPagas,
            'salario' => (float) EntradaSalario::sum('valor_salario'),
            'lancamentos' => $lancamentos->count(),
        ];
    }

    /**
     * Determine if a given lancamento is an expense or a gasto.
     */
    private function ehDespesa(Lancamento $lancamento): bool
    {
        return in_array($lancamento->tipo, ['despesa', 'gasto'], true);
    }
}
