<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RealRashid\SweetAlert\Facades\Alert;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, DashboardService $dashboardService)
    {
        $dadosValidados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);
        $competencia = isset($dadosValidados['mes'])
            ? \DateTime::createFromFormat('!Y-m', $dadosValidados['mes'])->format('m/Y')
            : now()->format('m/Y');

        try {
            return view('home.index', $dashboardService->getData($competencia));
        } catch (\Exception $ex) {
            Alert::toast('Erro! Contate o administrador do sistema.', 'error');

            return redirect()->back();
        }
    }
}
