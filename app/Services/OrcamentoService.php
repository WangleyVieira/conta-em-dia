<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Lancamento;
use App\Models\Orcamento;
use Carbon\Carbon;

class OrcamentoService
{
    public function getData(string $competencia): array
    {
        $gastosPorCategoria = Lancamento::query()
            ->selectRaw('categoria_id, SUM(valor) as total')
            ->where('competencia', $competencia)
            ->whereIn('tipo', ['despesa', 'gasto'])
            ->groupBy('categoria_id')
            ->pluck('total', 'categoria_id');

        $orcamentos = Orcamento::with('categoria')
            ->where('competencia', $competencia)
            ->get()
            ->map(function (Orcamento $orcamento) use ($gastosPorCategoria): array {
                $gasto = (float) $gastosPorCategoria->get($orcamento->categoria_id, 0);
                $limite = (float) $orcamento->valor_limite;
                $percentual = $limite > 0 ? ($gasto / $limite) * 100 : 0;

                return [
                    'id' => $orcamento->id,
                    'categoriaId' => $orcamento->categoria_id,
                    'categoria' => $orcamento->categoria?->descricao ?? 'Categoria removida',
                    'limite' => $limite,
                    'gasto' => $gasto,
                    'percentual' => $percentual,
                    'percentualBarra' => min(100, $percentual),
                    'status' => $percentual > 100
                        ? 'excedido'
                        : ($percentual >= 100 ? 'utilizado' : ($percentual >= 80 ? 'alerta' : 'dentro')),
                    'restante' => max(0, $limite - $gasto),
                ];
            });

        return [
            'competencia' => $competencia,
            'mesSelecionado' => Carbon::createFromFormat('!m/Y', $competencia),
            'categoriasDisponiveis' => Categoria::query()->orderBy('descricao')->get(['id', 'descricao']),
            'orcamentos' => $orcamentos,
        ];
    }

    public function getDashboardData(string $competencia): array
    {
        return $this->getData($competencia);
    }
}
