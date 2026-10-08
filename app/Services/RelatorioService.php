<?php

namespace App\Services;

use App\Models\EntradaSalario;
use App\Models\Lancamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioService
{
    public function getData(string $competencia): array
    {
        $mesSelecionado = Carbon::createFromFormat('!m/Y', $competencia);
        $competenciaAnterior = $mesSelecionado->copy()->subMonth()->format('m/Y');
        $competencias = collect(range(5, 0))
            ->map(fn (int $mesesAtras) => $mesSelecionado->copy()->subMonths($mesesAtras)->format('m/Y'));

        $lancamentos = Lancamento::with('categoria')
            ->whereIn('competencia', $competencias)
            ->get()
            ->groupBy('competencia');
        $salarios = EntradaSalario::query()
            ->whereIn('competencia', $competencias)
            ->get()
            ->groupBy('competencia');
        $lancamentosAnteriores = Lancamento::with('categoria')
            ->where('competencia', $competenciaAnterior)
            ->get();
        $salariosAnteriores = EntradaSalario::where('competencia', $competenciaAnterior)->get();

        $resumosMensais = $competencias->mapWithKeys(function (string $mes) use ($lancamentos, $salarios): array {
            return [$mes => $this->resumirMes(
                $lancamentos->get($mes, collect()),
                $salarios->get($mes, collect())
            )];
        });

        $resumoAtual = $resumosMensais->get($competencia, $this->resumoVazio());
        $resumoAnterior = $this->resumirMes(
            $lancamentosAnteriores,
            $salariosAnteriores
        );

        return [
            'competencia' => $competencia,
            'mesSelecionado' => $mesSelecionado,
            'resumo' => $resumoAtual,
            'resumoAnterior' => $resumoAnterior,
            'variacoes' => [
                'receitas' => $this->calcularVariacao($resumoAtual['receitas'], $resumoAnterior['receitas']),
                'despesas' => $this->calcularVariacao($resumoAtual['despesas'], $resumoAnterior['despesas']),
                'saldo' => $this->calcularVariacao($resumoAtual['saldo'], $resumoAnterior['saldo']),
            ],
            'categorias' => $this->montarComparativoCategorias(
                $lancamentos->get($competencia, collect()),
                $lancamentosAnteriores,
                $competencia,
                $competenciaAnterior
            ),
            'evolucao' => $resumosMensais->map(fn (array $mes, string $competenciaMes): array => [
                'mes' => Carbon::createFromFormat('!m/Y', $competenciaMes)->translatedFormat('M/y'),
                'receitas' => $mes['receitas'],
                'despesas' => $mes['despesas'],
                'saldo' => $mes['saldo'],
            ])->values(),
        ];
    }

    private function resumirMes(Collection $lancamentos, Collection $salarios): array
    {
        $receitas = (float) $salarios->sum('valor_salario');
        $despesas = 0.0;
        $despesasPagas = 0.0;
        $despesasPendentes = 0.0;

        foreach ($lancamentos as $lancamento) {
            if ($lancamento->tipo === 'receita') {
                $receitas += (float) $lancamento->valor;
                continue;
            }

            if (!in_array($lancamento->tipo, ['despesa', 'gasto'], true)) {
                continue;
            }

            $valor = (float) $lancamento->valor;
            $valorPago = (float) ($lancamento->valor_pago ?? 0);
            $despesas += $valor;
            $despesasPagas += $valorPago;
            $despesasPendentes += max(0, $valor - $valorPago);
        }

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'despesasPagas' => $despesasPagas,
            'despesasPendentes' => $despesasPendentes,
            'saldo' => $receitas - $despesas,
        ];
    }

    private function montarComparativoCategorias(
        Collection $lancamentosAtual,
        Collection $lancamentosAnterior,
        string $competencia,
        string $competenciaAnterior
    ): Collection
    {
        $totais = collect([$lancamentosAtual, $lancamentosAnterior])
            ->flatten()
            ->filter(fn (Lancamento $lancamento): bool => in_array($lancamento->tipo, ['despesa', 'gasto'], true))
            ->groupBy(fn (Lancamento $lancamento): string => $lancamento->categoria?->descricao ?? 'Sem categoria')
            ->map(fn (Collection $itens): array => [
                'atual' => (float) $itens
                    ->filter(fn (Lancamento $lancamento): bool => $lancamento->competencia === $competencia)
                    ->sum('valor'),
                'anterior' => (float) $itens
                    ->filter(fn (Lancamento $lancamento): bool => $lancamento->competencia === $competenciaAnterior)
                    ->sum('valor'),
            ]);

        return $totais->sortByDesc('atual');
    }

    private function calcularVariacao(float $atual, float $anterior): ?float
    {
        if ($anterior === 0.0) {
            return null;
        }

        return (($atual - $anterior) / abs($anterior)) * 100;
    }

    private function resumoVazio(): array
    {
        return [
            'receitas' => 0.0,
            'despesas' => 0.0,
            'despesasPagas' => 0.0,
            'despesasPendentes' => 0.0,
            'saldo' => 0.0,
        ];
    }
}
