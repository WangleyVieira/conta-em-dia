@extends('layout.main')

@section('content')
    <div class="container-fluid p-0 report-page">
        <div class="dashboard-hero d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="dashboard-kicker">ANÁLISE FINANCEIRA</span>
                <h1 class="h3 mb-1">Relatório mensal</h1>
                <span class="text-muted">Compare receitas e despesas por competência.</span>
            </div>
            <form method="GET" action="{{ route('relatorio.index') }}" class="report-month-filter">
                <label for="mes" class="sr-only">Mês do relatório</label>
                <input type="month" id="mes" name="mes" class="form-control" value="{{ $mesSelecionado->format('Y-m') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Aplicar
                </button>
            </form>
        </div>

        <div class="report-period-label mb-3">
            <i class="fas fa-calendar-alt mr-2"></i>
            {{ ucfirst($mesSelecionado->translatedFormat('F Y')) }}
            <span class="text-muted">comparado com {{ $mesSelecionado->copy()->subMonth()->translatedFormat('F Y') }}</span>
        </div>

        <div class="row mb-4">
            @php
                $cards = [
                    ['chave' => 'receitas', 'label' => 'Receitas', 'icon' => 'fa-arrow-up', 'cor' => 'success', 'favoravel' => 'up'],
                    ['chave' => 'despesas', 'label' => 'Despesas', 'icon' => 'fa-arrow-down', 'cor' => 'danger', 'favoravel' => 'down'],
                    ['chave' => 'saldo', 'label' => 'Saldo do mês', 'icon' => 'fa-wallet', 'cor' => $resumo['saldo'] < 0 ? 'danger' : 'primary', 'favoravel' => 'up'],
                ];
            @endphp
            @foreach ($cards as $card)
                @php($variacao = $variacoes[$card['chave']])
                @php($variacaoFavoravel = $variacao !== null && (($card['favoravel'] === 'up' && $variacao >= 0) || ($card['favoravel'] === 'down' && $variacao <= 0)))
                <div class="col-12 col-md-4 mb-3">
                    <div class="card report-kpi-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="text-muted">{{ $card['label'] }}</span>
                                <span class="dashboard-kpi-icon dashboard-kpi-icon-{{ $card['cor'] }}"><i class="fas {{ $card['icon'] }}"></i></span>
                            </div>
                            <h3 class="mb-2 {{ $card['chave'] === 'saldo' ? 'text-' . $card['cor'] : '' }}">
                                R$ {{ number_format($resumo[$card['chave']], 2, ',', '.') }}
                            </h3>
                            @if ($variacao === null)
                                <small class="text-muted">Sem base de comparação no mês anterior</small>
                            @else
                                <small class="{{ $variacaoFavoravel ? 'text-success' : 'text-danger' }}">
                                    <i class="fas {{ $variacao >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-1"></i>
                                    {{ number_format(abs($variacao), 1, ',', '.') }}% em relação ao mês anterior
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Evolução dos últimos seis meses</h5>
                        <small class="text-muted">Receitas, despesas e saldo por competência</small>
                    </div>
                    <div class="card-body">
                        <div class="report-chart"><canvas id="graficoEvolucao" aria-label="Gráfico de receitas, despesas e saldo mensais" role="img"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-1">Situação das despesas</h5>
                        <small class="text-muted">Na competência selecionada</small>
                    </div>
                    <div class="card-body">
                        <div class="report-expense-summary">
                            <div>
                                <span class="report-summary-dot report-summary-dot-paid"></span>
                                <span>Pago</span>
                                <strong>R$ {{ number_format($resumo['despesasPagas'], 2, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span class="report-summary-dot report-summary-dot-pending"></span>
                                <span>Pendente</span>
                                <strong>R$ {{ number_format($resumo['despesasPendentes'], 2, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="report-chart report-chart-doughnut">
                            <canvas id="graficoDespesas" aria-label="Gráfico das despesas pagas e pendentes" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">Despesas por categoria</h5>
                    <small class="text-muted">Comparativo com o mês anterior</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-right">{{ $mesSelecionado->translatedFormat('M/Y') }}</th>
                            <th class="text-right">{{ $mesSelecionado->copy()->subMonth()->translatedFormat('M/Y') }}</th>
                            <th class="text-right">Variação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categorias as $categoria => $valores)
                            @php($diferenca = $valores['atual'] - $valores['anterior'])
                            <tr>
                                <td class="font-weight-bold">{{ $categoria }}</td>
                                <td class="text-right">R$ {{ number_format($valores['atual'], 2, ',', '.') }}</td>
                                <td class="text-right">R$ {{ number_format($valores['anterior'], 2, ',', '.') }}</td>
                                <td class="text-right {{ $diferenca < 0 ? 'text-success' : ($diferenca > 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ $diferenca > 0 ? '+' : '' }}R$ {{ number_format($diferenca, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Não há despesas para comparar nessas competências.</td></tr>
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
            var corTexto = temaEscuro ? '#9ca3af' : '#64748b';
            var corGrade = temaEscuro ? '#374151' : '#e5e7eb';
            var evolucao = @json($evolucao);

            new Chart(document.getElementById('graficoEvolucao'), {
                type: 'bar',
                data: {
                    labels: evolucao.map(function (mes) { return mes.mes; }),
                    datasets: [
                        { label: 'Receitas', data: evolucao.map(function (mes) { return mes.receitas; }), backgroundColor: '#10b981' },
                        { label: 'Despesas', data: evolucao.map(function (mes) { return mes.despesas; }), backgroundColor: '#ef4444' },
                        { label: 'Saldo', data: evolucao.map(function (mes) { return mes.saldo; }), type: 'line', borderColor: '#3b82f6', backgroundColor: 'transparent', fill: false }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { labels: { fontColor: corTexto } },
                    scales: {
                        yAxes: [{ ticks: { beginAtZero: true, fontColor: corTexto }, gridLines: { color: corGrade } }],
                        xAxes: [{ ticks: { fontColor: corTexto }, gridLines: { display: false } }]
                    }
                }
            });

            new Chart(document.getElementById('graficoDespesas'), {
                type: 'doughnut',
                data: {
                    labels: ['Pago', 'Pendente'],
                    datasets: [{ data: [{{ $resumo['despesasPagas'] }}, {{ $resumo['despesasPendentes'] }}], backgroundColor: ['#10b981', '#f59e0b'], borderWidth: 0 }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { position: 'bottom', labels: { fontColor: corTexto } },
                    cutoutPercentage: 68
                }
            });
        });
    </script>
@endsection
