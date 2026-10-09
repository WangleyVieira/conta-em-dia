<?php

namespace App\Services;

use App\Models\EntradaSalario;
use App\Models\Lancamento;
use App\Models\Orcamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly OrcamentoService $orcamentoService) {}

    /**
     * Get the data for the dashboard.
     */
    public function getData(?string $competencia = null): array
    {
        $competencia ??= now()->format('m/Y');
        $lancamentos = $this->buscarLancamentos($competencia);
        $despesasPagas = $this->calcularDespesasPagas($lancamentos);
        $pendente = $this->calcularValorPendente($lancamentos);
        $orcamentoMensal = $this->orcamentoService->getDashboardData($competencia);

        return [
            'competencia' => $competencia,
            'competenciasDisponiveis' => $this->listarCompetenciasDisponiveis($competencia),
            'resumo' => $this->montarResumo($lancamentos, $despesasPagas, $pendente, $competencia),
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
     * Fetch lancamentos for the selected competencia, ordered by creation date descending.
     */
    private function buscarLancamentos(string $competencia): Collection
    {
        return Lancamento::with('categoria')
            ->where('competencia', $competencia)
            ->orderByDesc('created_at')
            ->get();
    }

    private function listarCompetenciasDisponiveis(string $competenciaAtual): Collection
    {
        return collect()
            ->merge(Lancamento::query()->select('competencia')->distinct()->pluck('competencia'))
            ->merge(EntradaSalario::query()->select('competencia')->distinct()->pluck('competencia'))
            ->merge(Orcamento::query()->select('competencia')->distinct()->pluck('competencia'))
            ->push($competenciaAtual)
            ->unique()
            ->sortByDesc(fn (string $mes): int => Carbon::createFromFormat('!m/Y', $mes)->getTimestamp())
            ->values();
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
     * Calculate the unpaid amount for expenses that are still pending.
     */
    private function calcularValorPendente($lancamentos): float
    {
        $total = 0;

        foreach ($lancamentos as $lancamento) {
            if (! $this->ehDespesa($lancamento)) {
                continue;
            }

            $valor = (float) $lancamento->valor;
            $valorPago = (float) ($lancamento->valor_pago ?? 0);
            if ($lancamento->situacao === 'pendente') {
                $total += max(0, $valor - $valorPago);
            }
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
            if (! $this->ehDespesa($lancamento)) {
                continue;
            }

            $categoria = $lancamento->categoria?->descricao ?? 'Sem categoria';
            $totais[$categoria] = ($totais[$categoria] ?? 0) + (float) ($lancamento->valor_pago ?? 0);
        }

        arsort($totais);

        return collect($totais);
    }

    /**
     * Mount the summary data for the selected dashboard competencia.
     */
    private function montarResumo(
        Collection $lancamentos,
        float $despesasPagas,
        float $pendente,
        string $competencia
    ): array {
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
            'salario' => (float) EntradaSalario::query()
                ->where('competencia', $competencia)
                ->sum('valor_salario'),
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
