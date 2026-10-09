<?php

namespace App\Http\Controllers;

use App\Http\Requests\LancamentoRequest;
use App\Models\Categoria;
use App\Models\Lancamento;
use App\Services\LancamentoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class LancamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, LancamentoService $lancamentoService)
    {
        $filtrosValidados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'competencia' => ['nullable', 'date_format:m/Y'],
        ]);

        if (isset($filtrosValidados['mes'])) {
            $request->merge([
                'competencia' => Carbon::createFromFormat('!Y-m', $filtrosValidados['mes'])->format('m/Y'),
            ]);
        } elseif (! isset($filtrosValidados['competencia'])) {
            $request->merge(['competencia' => now()->format('m/Y')]);
        }

        try {
            return view('lancamento.index', $lancamentoService->getIndexData($request));
        } catch (\Exception $ex) {
            Alert::toast('Erro ao carregar os lançamentos.', 'error');

            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        try {
            $categorias = Categoria::get();

            return view('lancamento.form', compact('categorias'));

        } catch (\Exception $ex) {
            Alert::toast('Erro ao carregar o formulário.', 'error');

            return redirect()->back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(LancamentoRequest $request)
    {
        try {
            Lancamento::create($request->validated() + [
                'cadastrado_por_usuario' => Auth::user()->id,
            ]);
            Alert::toast('Lançamento cadastrado com sucesso!', 'success');

            return redirect()->route('lancamento.index');
        } catch (\Exception $ex) {
            Alert::toast('Erro ao cadastrar o lançamento.', 'error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        try {
            return view('lancamento.form', [
                'lancamento' => Lancamento::findOrFail($id),
                'categorias' => Categoria::get(),
            ]);
        } catch (\Exception $ex) {
            Alert::toast('Lançamento não encontrado.', 'error');

            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(LancamentoRequest $request, $id)
    {
        try {
            Lancamento::findOrFail($id)->update($request->validated());
            Alert::toast('Lançamento atualizado com sucesso!', 'success');

            return redirect()->route('lancamento.index');
        } catch (\Exception $ex) {
            Alert::toast('Erro ao atualizar o lançamento.', 'error');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        try {
            Lancamento::findOrFail($id)->delete();
            Alert::toast('Lançamento excluído com sucesso!', 'success');

            return redirect()->route('lancamento.index');
        } catch (\Exception $ex) {
            Alert::toast('Erro ao excluir o lançamento.', 'error');

            return redirect()->back();
        }
    }

    /**
     * Generate the next period for the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function gerarProximaCompetencia($id)
    {
        try {
            $lancamento = Lancamento::findOrFail($id);

            if (! $lancamento->is_fixo) {
                Alert::toast('Apenas lançamentos fixos podem ser recorrentes.', 'error');

                return redirect()->back();
            }

            $proximaCompetencia = Carbon::createFromFormat('m/Y', $lancamento->competencia)->addMonth();
            $jaExiste = Lancamento::query()
                ->where('competencia', $proximaCompetencia->format('m/Y'))
                ->where('descricao', $lancamento->descricao)
                ->where('categoria_id', $lancamento->categoria_id)
                ->exists();

            if ($jaExiste) {
                Alert::toast('Esse lançamento já existe na próxima competência.', 'info');

                return redirect()->route('lancamento.index', ['competencia' => $proximaCompetencia->format('m/Y')]);
            }

            $novoLancamento = $lancamento->replicate([
                'competencia', 'data_vencimento', 'valor_pago', 'is_pago', 'data_pagamento', 'created_at', 'updated_at',
            ]);
            $novoLancamento->competencia = $proximaCompetencia->format('m/Y');
            $novoLancamento->data_vencimento = $proximaCompetencia->copy()->day(min(
                $lancamento->data_vencimento->day,
                $proximaCompetencia->daysInMonth
            ));
            $novoLancamento->valor_pago = null;
            $novoLancamento->is_pago = false;
            $novoLancamento->data_pagamento = null;
            $novoLancamento->save();

            Alert::toast('Próxima competência gerada com sucesso!', 'success');

            return redirect()->route('lancamento.index', ['competencia' => $novoLancamento->competencia]);
        } catch (\Exception $ex) {
            Alert::toast('Erro ao gerar a próxima competência.', 'error');

            return redirect()->back();
        }
    }
}
