@extends('layout.main')

@section('content')
    @include('sweetalert::alert')

    <div class="container-fluid p-0 budget-page">
        <div class="dashboard-hero d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="dashboard-kicker">PLANEJAMENTO FINANCEIRO</span>
                <h1 class="h3 mb-1">Orçamentos mensais</h1>
                <span class="text-muted">Defina limites compartilhados por categoria. O dashboard avisa a partir de 80%.</span>
            </div>
            <form method="GET" action="{{ route('orcamento.index') }}" class="report-month-filter">
                <label for="mes" class="sr-only">Mês dos orçamentos</label>
                <input type="month" id="mes" name="mes" class="form-control" value="{{ $mesSelecionado->format('Y-m') }}">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Aplicar
                </button>
            </form>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Definir limite</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('orcamento.store') }}" class="row align-items-end">
                    @csrf
                    <input type="hidden" name="competencia" value="{{ $competencia }}">
                    <div class="form-group col-md-5">
                        <label for="categoria_id">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-control select2 @error('categoria_id') is-invalid @enderror" required>
                            <option value="">Selecione uma categoria</option>
                            @foreach ($categoriasDisponiveis as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                                    {{ $categoria->descricao }}
                                </option>
                            @endforeach
                        </select>
                        @error('categoria_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="valor_limite">Limite mensal (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" id="valor_limite" name="valor_limite" inputmode="decimal"
                                value="{{ old('valor_limite') }}"
                                class="form-control valor @error('valor_limite') is-invalid @enderror"
                                placeholder="Ex.: 800,00" required>
                        </div>
                        @error('valor_limite')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <button type="submit" class="btn btn-success mb-0">
                            <i class="fas fa-save mr-1"></i> Salvar limite
                        </button>
                    </div>
                </form>
                <small class="text-muted">Salvar outra vez para a mesma categoria substitui o limite. Os valores consideram as despesas lançadas na competência.</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">Limites de {{ $mesSelecionado->translatedFormat('F Y') }}</h5>
                <small class="text-muted">Há um aviso ao atingir 80%; em 100% o limite está utilizado e acima de 100% está ultrapassado.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-right">Gasto</th>
                            <th class="text-right">Limite</th>
                            <th>Utilização</th>
                            <th>Status</th>
                            <th class="text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orcamentos as $orcamento)
                            @php
                                $corOrcamento = match ($orcamento['status']) {
                                    'excedido' => 'danger',
                                    'utilizado', 'alerta' => 'warning',
                                    default => 'success',
                                };
                            @endphp
                            <tr>
                                <td class="font-weight-bold">{{ $orcamento['categoria'] }}</td>
                                <td class="text-right">R$ {{ number_format($orcamento['gasto'], 2, ',', '.') }}</td>
                                <td class="text-right">R$ {{ number_format($orcamento['limite'], 2, ',', '.') }}</td>
                                <td>
                                    <div class="budget-table-progress">
                                        <div class="progress budget-progress" role="progressbar"
                                            aria-label="Uso do orçamento de {{ $orcamento['categoria'] }}"
                                            aria-valuenow="{{ min(100, round($orcamento['percentual'])) }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar bg-{{ $corOrcamento }}"
                                                style="width: {{ $orcamento['percentualBarra'] }}%"></div>
                                        </div>
                                        <small>{{ number_format($orcamento['percentual'], 1, ',', '.') }}%</small>
                                    </div>
                                </td>
                                <td>
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
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-danger btn-sm"
                                        data-toggle="modal" data-target="#modalExcluirOrcamento{{ $orcamento['id'] }}"
                                        aria-label="Remover orçamento de {{ $orcamento['categoria'] }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <div class="modal fade" id="modalExcluirOrcamento{{ $orcamento['id'] }}" tabindex="-1"
                                role="dialog" aria-labelledby="modalLabelExcluirOrcamento{{ $orcamento['id'] }}"
                                aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('orcamento.destroy', $orcamento['id']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-header btn-danger">
                                                <h5 class="modal-title" id="modalLabelExcluirOrcamento{{ $orcamento['id'] }}">
                                                    <strong>Remover orçamento</strong>
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Deseja remover o orçamento de
                                                <strong>{{ $orcamento['categoria'] }}</strong> para
                                                {{ $mesSelecionado->translatedFormat('F Y') }}?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-danger">
                                                    Remover
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nenhum limite cadastrado para esta competência.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            $('#categoria_id').select2({
                language: {
                    noResults: function () {
                        return 'Nenhuma categoria encontrada';
                    }
                },
                width: '100%'
            });

            $('.valor').mask('#.##0,00', { reverse: true });
        });
    </script>
@endsection
