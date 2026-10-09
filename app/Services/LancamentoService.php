<?php

namespace App\Services;

use App\Models\EntradaSalario;
use App\Models\Lancamento;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LancamentoService
{
    /**
     * Get the data for the index view, including filtered lancamentos and summary information.
     */
    public function getIndexData(Request $request): array
    {
        $competencia = $request->input('competencia');
        $situacao = $request->input('situacao');
        $competencias = $this->listarCompetencias();
        $lancamentos = $this->listarLancamentos($competencia);
        $lancamentos = $this->filtrarPorSituacao($lancamentos, $situacao);
        $resumo = $this->calcularResumo($lancamentos, $competencia);

        return [
            'lancamentos' => $lancamentos,
            'competencia' => $competencia,
            'situacao' => $situacao,
            'resumo' => $resumo,
            'competencias' => $competencias,
        ];
    }

    /**
     * List distinct competencias from the Lancamento model.
     */
    private function listarCompetencias(): Collection
    {
        return Lancamento::query()
            ->select('competencia')
            ->distinct()
            ->pluck('competencia');
    }

    /**
     * List lancamentos, optionally filtered by competencia.
     */
    private function listarLancamentos(?string $competencia): Collection
    {
        $consulta = Lancamento::with('categoria');

        if ($competencia) {
            $consulta->where('competencia', $competencia);
        }

        return $consulta->orderBy('data_vencimento')->get();
    }

    /**
     * Filter lancamentos by situacao if provided.
     */
    private function filtrarPorSituacao(Collection $lancamentos, ?string $situacao): Collection
    {
        if (!$situacao) {
            return $lancamentos;
        }

        $lancamentosFiltrados = collect();

        foreach ($lancamentos as $lancamento) {
            $situacaoDoLancamento = $lancamento->situacao;

            if ($situacaoDoLancamento === $situacao) {
                $lancamentosFiltrados->push($lancamento);
            }
        }

        return $lancamentosFiltrados;
    }

    /**
     * Calculate summary information for the given lancamentos, including total receitas, despesas, paid, pending, and saldo.
     */
    private function calcularResumo(Collection $lancamentos, ?string $competencia): array
    {
        $totalReceitas = 0;
        $totalDespesas = 0;
        $totalPago = 0;
        $totalPendente = 0;

        foreach ($lancamentos as $lancamento) {
            $valorPrevisto = (float) $lancamento->valor;
            $valorPago = (float) ($lancamento->valor_pago ?? 0);

            if ($lancamento->tipo === 'receita') {
                $totalReceitas += $valorPrevisto;
            }

            if ($this->lancamentoEhDespesa($lancamento)) {
                $totalDespesas += $valorPrevisto;
            }

            $totalPago += $valorPago;

            if ($this->lancamentoEhDespesa($lancamento) && $lancamento->situacao === 'pendente') {
                $totalPendente += max(0, $valorPrevisto - $valorPago);
            }
        }

        $competenciaDoSalario = $this->definirCompetenciaDoSalario($competencia);
        $salarioDaCompetencia = (float) EntradaSalario::query()
            ->where('competencia', $competenciaDoSalario)
            ->sum('valor_salario');

        return [
            'receitas' => $totalReceitas,
            'despesas' => $totalDespesas,
            'pago' => $totalPago,
            'pendente' => $totalPendente,
            'saldo_entrada_salario' => $salarioDaCompetencia,
            'saldo' => $salarioDaCompetencia - $totalDespesas,
        ];
    }

    /**
     * Define the competencia for salary entries, defaulting to the current month/year if not provided.
     */
    private function definirCompetenciaDoSalario(?string $competencia): string
    {
        return $competencia ?: now()->format('m/Y');
    }

    /**
     * Determine if a lancamento is an expense based on its type.
     */
    private function lancamentoEhDespesa(Lancamento $lancamento): bool
    {
        return in_array($lancamento->tipo, ['despesa', 'gasto'], true);
    }
}
