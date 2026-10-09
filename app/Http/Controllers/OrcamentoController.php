<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Services\OrcamentoService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class OrcamentoController extends Controller
{
    public function index(Request $request, OrcamentoService $orcamentoService)
    {
        $dadosValidados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);
        $competencia = isset($dadosValidados['mes'])
            ? \DateTime::createFromFormat('!Y-m', $dadosValidados['mes'])->format('m/Y')
            : now()->format('m/Y');

        return view('orcamento.index', $orcamentoService->getData($competencia));
    }

    public function store(Request $request)
    {
        $valorLimite = $request->input('valor_limite');

        if (is_string($valorLimite) && str_contains($valorLimite, ',')) {
            $valorLimite = str_replace(',', '.', str_replace('.', '', $valorLimite));
            $request->merge(['valor_limite' => $valorLimite]);
        }

        $dados = $request->validate([
            'competencia' => ['required', 'date_format:m/Y'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'valor_limite' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ]);

        Orcamento::updateOrCreate(
            [
                'categoria_id' => $dados['categoria_id'],
                'competencia' => $dados['competencia'],
            ],
            ['valor_limite' => $dados['valor_limite']]
        );

        Alert::toast('Orçamento salvo com sucesso!', 'success');

        return redirect()->route('orcamento.index', [
            'mes' => \DateTime::createFromFormat('!m/Y', $dados['competencia'])->format('Y-m'),
        ]);
    }

    public function destroy(Orcamento $orcamento)
    {
        $competencia = \DateTime::createFromFormat('!m/Y', $orcamento->competencia)->format('Y-m');
        $orcamento->delete();

        Alert::toast('Orçamento removido com sucesso!', 'success');

        return redirect()->route('orcamento.index', ['mes' => $competencia]);
    }
}
