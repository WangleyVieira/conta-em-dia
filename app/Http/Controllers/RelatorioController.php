<?php

namespace App\Http\Controllers;

use App\Services\RelatorioService;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function index(Request $request, RelatorioService $relatorioService)
    {
        $dadosValidados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $competencia = isset($dadosValidados['mes'])
            ? \DateTime::createFromFormat('!Y-m', $dadosValidados['mes'])->format('m/Y')
            : now()->format('m/Y');

        return view('relatorio.index', $relatorioService->getData($competencia));
    }
}
