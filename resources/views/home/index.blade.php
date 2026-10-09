@extends('layout.main')

@section('content')
    @include('sweetalert::alert')

    <div class="container-fluid p-0 dashboard-page">
        <div class="dashboard-hero d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="dashboard-kicker">VISÃO GERAL · {{ \Carbon\Carbon::createFromFormat('!m/Y', $competencia)->translatedFormat('F Y') }}</span>
                <h1 class="h3 mb-1"><strong>Seu dinheiro</strong> em dia</h1>
                <span class="text-muted">Acompanhe sua vida financeira nesta competência.</span>
            </div>
            <div class="dashboard-actions d-flex align-items-center">
                <form method="GET" action="{{ route('dashboard') }}" class="report-month-filter">
                    <label for="mes" class="sr-only">Competência do dashboard</label>
                    <input type="month" id="mes" name="mes" class="form-control"
                        value="{{ \Carbon\Carbon::createFromFormat('!m/Y', $competencia)->format('Y-m') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i> Aplicar
                    </button>
                </form>
                <a href="{{ route('lancamento.create') }}" class="btn btn-success dashboard-new-entry">
                    <i class="fas fa-plus-square" aria-hidden="true"></i>
                    <span>Novo lançamento</span>
                </a>
            </div>
        </div>

        <div class="row mb-4">
            @php
                $cards = [
                    ['label' => 'Despesas da competência', 'value' => $resumo['despesas'], 'color' => 'danger', 'icon' => 'fa-arrow-down'],
                    ['label' => 'Pendente da competência', 'value' => $resumo['pendente'], 'color' => 'warning', 'icon' => 'fa-clock'],
                    ['label' => 'Saldo da competência', 'value' => $resumo['saldo'], 'color' => $resumo['saldo'] < 0 ? 'danger' : 'success', 'icon' => 'fa-wallet'],
                    ['label' => 'Salário da competência', 'value' => $resumo['salario'], 'color' => 'primary', 'icon' => 'fa-money-bill-wave'],
                ];
            @endphp
            @foreach ($cards as $card)
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <div class="card dashboard-kpi-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <small class="text-muted">{{ $card['label'] }}</small>
                                <span class="dashboard-kpi-icon dashboard-kpi-icon-{{ $card['color'] }}"><i class="fas {{ $card['icon'] }}"></i></span>
                            </div>
                            <h3 class="mb-1 text-{{ $card['color'] }}">R$ {{ number_format((float) $card['value'], 2, ',', '.') }}</h3>
                            <small class="text-muted">
                                @if ($card['label'] === 'Pendente da competência')
                                    Acompanhe seus próximos pagamentos
                                @elseif ($card['label'] === 'Saldo da competência')
                                    Receitas menos despesas pagas
                                @else
                                    Referente a {{ \Carbon\Carbon::createFromFormat('!m/Y', $competencia)->translatedFormat('F Y') }}
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Orçamento mensal</h5>
                    <small class="text-muted">
                        {{ $orcamentoMensal['mesSelecionado']->translatedFormat('F Y') }} · aviso aos 80%; ultrapassado acima de 100%
                    </small>
                </div>
                <a href="{{ route('orcamento.index', ['mes' => $orcamentoMensal['mesSelecionado']->format('Y-m')]) }}"
                    class="btn btn-sm btn-outline-primary">Gerenciar</a>
            </div>
            <div class="card-body">
                @if ($orcamentoMensal['orcamentos']->isEmpty())
                    <p class="text-muted mb-0">
                        Nenhum limite definido para este mês.
                        <a href="{{ route('orcamento.index', ['mes' => \Carbon\Carbon::createFromFormat('!m/Y', $competencia)->format('Y-m')]) }}">Configure seus orçamentos por categoria.</a>
                    </p>
                @else
                    <div class="row">
                        @foreach ($orcamentoMensal['orcamentos'] as $orcamento)
                            @php
                                $corOrcamento = match ($orcamento['status']) {
                                    'excedido' => 'danger',
                                    'utilizado', 'alerta' => 'warning',
                                    default => 'success',
                                };
                            @endphp
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="budget-summary-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>{{ $orcamento['categoria'] }}</strong>
                                        <span class="badge badge-{{ $corOrcamento }}">
                                            @if ($orcamento['status'] === 'excedido')
                                                Limite ultrapassado
                                            @elseif ($orcamento['status'] === 'utilizado')
                                                Limite utilizado
                                            @elseif ($orcamento['status'] === 'alerta')
                                                Próximo do limite
                                            @else
                                                Dentro do limite
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Gasto: R$ {{ number_format($orcamento['gasto'], 2, ',', '.') }}</span>
                                        <span>Limite: R$ {{ number_format($orcamento['limite'], 2, ',', '.') }}</span>
                                    </div>
                                    <div class="progress budget-progress" role="progressbar"
                                        aria-label="Uso do orçamento de {{ $orcamento['categoria'] }}"
                                        aria-valuenow="{{ min(100, round($orcamento['percentual'])) }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-{{ $corOrcamento }}"
                                            style="width: {{ $orcamento['percentualBarra'] }}%"></div>
                                    </div>
                                    <small class="text-muted">
                                        {{ number_format($orcamento['percentual'], 1, ',', '.') }}% utilizado
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Despesas por categoria</h5>
                        <span class="text-muted small">{{ $totalLancamentosCategorias }} {{ $totalLancamentosCategorias === 1 ? 'lançamento' : 'lançamentos' }}</span>
                    </div>
                    <div class="card-body">
                        @if ($categorias->isEmpty())
                            <p class="text-muted text-center py-5 mb-0">Ainda não há despesas cadastradas.</p>
                        @else
                            <div class="dashboard-chart dashboard-chart-category"><canvas id="graficoCategorias"></canvas></div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Categoria</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($categorias as $categoria => $total)
                                            <tr>
                                                <td>{{ $categoria }}</td>
                                                <td class="text-right font-weight-bold">R$ {{ number_format((float) $total, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Comparativo de despesas</h5>
                        <small class="text-muted">Pago x pendente</small>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="dashboard-chart dashboard-chart-comparison"><canvas id="graficoComparativo"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Lançamentos de {{ \Carbon\Carbon::createFromFormat('!m/Y', $competencia)->translatedFormat('F Y') }}</h5>
                <a href="{{ route('lancamento.index', ['competencia' => $competencia]) }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 dashboard-recent-table">
                    <thead style="background-color:#e2e7e6">
                        <tr><th>Descrição</th><th>Categoria</th><th>Vencimento</th><th>Valor</th><th>Situação</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($lancamentosRecentes as $lancamento)
                            @php($classesSituacao = ['pago' => 'success', 'pendente' => 'secondary', 'vencido' => 'danger'])
                            <tr>
                                <td class="font-weight-bold">{{ $lancamento->descricao }}</td>
                                <td>{{ $lancamento->categoria?->descricao ?? '-' }}</td>
                                <td>{{ $lancamento->data_vencimento?->format('d/m/Y') ?? '-' }}</td>
                                <td>R$ {{ number_format((float) $lancamento->valor, 2, ',', '.') }}</td>
                                <td><span class="badge badge-{{ $classesSituacao[$lancamento->situacao] }}">{{ ucfirst($lancamento->situacao) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum lançamento cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script>
        $(function () {
            var temaEscuro = document.documentElement.classList.contains('dark-theme');
            var corTextoGrafico = temaEscuro ? '#9ca3af' : '#64748b';
            var corGradeGrafico = temaEscuro ? '#374151' : '#e5e7eb';
            var categoriaCanvas = document.getElementById('graficoCategorias');
            if (categoriaCanvas) {
                new Chart(categoriaCanvas, {
                    type: 'pie',
                    data: {
                        labels: @json($categorias->keys()->values()),
                        datasets: [{
                            data: @json($categorias->values()->values()),
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: {
                            position: 'bottom',
                            labels: { fontColor: corTextoGrafico }
                        }
                    }
                });
            }

            new Chart(document.getElementById('graficoComparativo'), {
                type: 'bar',
                data: {
                    labels: ['Despesas pagas', 'Despesas pendentes'],
                    datasets: [{
                        data: [{{ $comparativo['pago'] }}, {{ $comparativo['pendente'] }}],
                        backgroundColor: ['#10b981', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { display: false },
                    scales: {
                        yAxes: [{
                            ticks: { beginAtZero: true, fontColor: corTextoGrafico },
                            gridLines: { color: corGradeGrafico }
                        }],
                        xAxes: [{
                            ticks: { fontColor: corTextoGrafico },
                            gridLines: { color: corGradeGrafico }
                        }]
                    }
                }
            });
        });
    </script>
@endsection
