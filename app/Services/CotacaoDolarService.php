<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class CotacaoDolarService
{
    public function obter(): array
    {
        return Cache::remember('cotacao.usd-brl', now()->addSeconds(30), function (): array {
            $resposta = Http::acceptJson()
                ->withOptions([
                    'verify' => config('services.cotacao_dolar.ca_bundle') ?: true,
                ])
                ->connectTimeout(3)
                ->timeout(5)
                ->get('https://economia.awesomeapi.com.br/json/last/USD-BRL')
                ->throw();

            $cotacao = $resposta->json('USDBRL');

            if (
                !is_array($cotacao)
                || !isset($cotacao['bid'], $cotacao['ask'], $cotacao['pctChange'])
                || !is_numeric($cotacao['bid'])
                || !is_numeric($cotacao['ask'])
                || !is_numeric($cotacao['pctChange'])
                || !isset($cotacao['timestamp'])
                || !is_numeric($cotacao['timestamp'])
            ) {
                throw new UnexpectedValueException('A resposta da API de cotação do dólar é inválida.');
            }

            return [
                'compra' => (float) $cotacao['bid'],
                'venda' => (float) $cotacao['ask'],
                'variacao' => (float) $cotacao['pctChange'],
                'atualizadoEm' => (int) $cotacao['timestamp'],
            ];
        });
    }
}
