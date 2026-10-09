@extends('layout.main')

@section('content')
    @include('sweetalert::alert')

    <div class="card lancamento-page" style="background-color:white">
        <div class="card-header lancamento-page-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Lançamentos</h4>
                <small class="text-muted">Receitas, despesas e pagamentos de {{ ucfirst($mesSelecionado->translatedFormat('F Y')) }}</small>
            </div>
            <div class="lancamento-page-actions">
                <form method="GET" action="{{ route('lancamento.index') }}" class="report-month-filter">
                    <label for="mes" class="sr-only">Mês dos lançamentos</label>
                    <input type="month" id="mes" name="mes" class="form-control" value="{{ $mesSelecionado->format('Y-m') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1" aria-hidden="true"></i> Aplicar
                    </button>
                </form>
                <a class="btn btn-success lancamento-new-entry" href="{{ route('lancamento.create') }}">
                    <i class="fas fa-plus-square" aria-hidden="true"></i>
                    <span>Novo lançamento</span>
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-4" style="margin-right: -10px; margin-left: -10px;">
                <div class="col-md-3 px-2 mb-2">
                    <div class="card h-100 border border-secondary-subtle shadow-sm"
                        style="background: rgba(255,255,255,1); border-radius: 18px; min-height: 120px;">
                        <div class="card-body py-3 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <small class="text-muted mb-0">Despesas da competência</small>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background: rgba(239,68,68,0.12); color: #ef4444; width: 28px; height: 28px; font-size: 12px;">
                                    <i class="fas fa-arrow-down" aria-hidden="true"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 text-danger font-weight-bold">R$ {{ number_format($resumo['despesas'], 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 px-2 mb-2">
                    <div class="card h-100 border border-secondary-subtle shadow-sm"
                        style="background: rgba(255,255,255,1); border-radius: 18px; min-height: 120px;">
                        <div class="card-body py-3 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <small class="text-muted mb-0">Pendente da competência</small>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background: rgba(245,158,11,0.12); color: #f59e0b; width: 28px; height: 28px; font-size: 12px;">
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 text-warning font-weight-bold">R$ {{ number_format($resumo['pendente'], 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 px-2 mb-2">
                    <div class="card h-100 border border-secondary-subtle shadow-sm"
                        style="background: rgba(255,255,255,1); border-radius: 18px; min-height: 120px;">
                        <div class="card-body py-3 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <small class="text-muted mb-0">Saldo da competência</small>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background: rgba(59,130,246,0.12); color: #3b82f6; width: 28px; height: 28px; font-size: 12px;">
                                    <i class="fas fa-wallet" aria-hidden="true"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 {{ $resumo['saldo'] < 0 ? 'text-danger' : 'text-success' }} font-weight-bold">R$ {{ number_format($resumo['saldo'], 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 px-2 mb-2">
                    <div class="card h-100 border border-secondary-subtle shadow-sm"
                        style="background: rgba(255,255,255,1); border-radius: 18px; min-height: 120px;">
                        <div class="card-body py-3 d-flex flex-column justify-content-center">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <small class="text-muted mb-0">Salário da competência</small>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="background: rgba(139,92,246,0.12); color: #8b5cf6; width: 28px; height: 28px; font-size: 12px;">
                                    <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 text-primary font-weight-bold">R$ {{ number_format((float) $resumo['saldo_entrada_salario'], 2, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            @if ($lancamentos->isEmpty())
                <div>
                    <h1 class="alert-info px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        Não há lançamentos para {{ $mesSelecionado->translatedFormat('F Y') }}.
                    </h1>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead style="background-color:#e2e7e6">
                            <tr>
                                <th>Venc.</th>
                                <th>Pago em</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Previsto</th>
                                <th>Pago</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lancamentos as $lancamento)
                                @php($classesSituacao = ['pago' => 'success', 'pendente' => 'secondary', 'vencido' => 'danger'])
                                <tr>
                                    <td>{{ $lancamento->data_vencimento?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $lancamento->data_pagamento?->format('d/m/Y') ?? '-' }}</td>
                                    <td>{{ $lancamento->descricao }}</td>
                                    <td>{{ $lancamento->categoria?->descricao }}</td>
                                    <td>R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</td>
                                    <td>{{ $lancamento->valor_pago !== null ? 'R$ ' . number_format($lancamento->valor_pago, 2, ',', '.') : '-' }}</td>
                                    <td><span class="badge badge-{{ $classesSituacao[$lancamento->situacao] }}">{{ ucfirst($lancamento->situacao) }}</span></td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-warning btn-sm" href="{{ route('lancamento.edit', $lancamento->id) }}" title="Editar">
                                            <i class="fas fa-pen"></i></a>
                                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                                data-target="#modalExcluir{{ $lancamento->id }}"><i class="fas fa-trash"></i>
                                            </button>
                                        @if ($lancamento->is_fixo)
                                            <form class="d-inline" method="POST" action="{{ route('lancamento.gerar_proxima_competencia', $lancamento->id) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm" type="submit" title="Gerar próxima competência"><i class="fas fa-calendar-plus"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                <div class="modal fade" id="modalExcluir{{ $lancamento->id }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabelExcluir" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <form method="POST" class="form_prevent_multiple_submits"
                                                action="{{ route('lancamento.destroy', $lancamento->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <div class="modal-header btn-danger">
                                                    <h5 class="modal-title text-center" id="exampleModalLabelExcluir">
                                                        <strong>Excluir Lançamento</strong>
                                                    </h5>
                                                </div>
                                                <div class="modal-body">
                                                    Deseja excluir o lançamento: <strong>{{ $lancamento->descricao }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancelar
                                                    </button>
                                                    <button type="submit"class="button_submit btn btn-danger">Excluir</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
