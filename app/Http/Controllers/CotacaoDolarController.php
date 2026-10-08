<?php

namespace App\Http\Controllers;

use App\Services\CotacaoDolarService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use UnexpectedValueException;

class CotacaoDolarController extends Controller
{
    public function show(CotacaoDolarService $cotacaoDolarService)
    {
        try {
            return Response::json($cotacaoDolarService->obter());
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('Não foi possível atualizar a cotação do dólar.', [
                'exception' => $exception::class,
                'message' => Str::limit($exception->getMessage(), 300),
            ]);

            return Response::json([
                'message' => 'A cotação do dólar está temporariamente indisponível.',
            ], 503);
        }
    }
}
