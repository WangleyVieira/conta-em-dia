<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>Conta em dia</title>
        <meta name="description" content="Sistema de controle financeiro pessoal — gerencie lançamentos, categorias e mais.">
        <link rel="icon" type="image/svg+xml" href="{{ asset('img/conta-em-dia-logo.svg') }}">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
        <link rel="stylesheet" href="{{ asset('css/fontawesome.css') }}">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
        {{-- Custom premium theme (overrides AdminKit defaults) --}}
        <link rel="stylesheet" href="{{ asset('css/style-custom.css') }}">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.11.0/r-2.2.9/rr-1.2.8/datatables.min.css"/>
        <link href="{{asset('select2-4.1.0/dist/css/select2.min.css')}}" rel="stylesheet" />
        <link rel="stylesheet" href="{{asset('select2-bootstrap/dist/select2-bootstrap.css')}}"/>
        <script>
            if (localStorage.getItem('controle-financas-theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        </script>
        <script src="{{ asset('js/jquery.js') }}"></script>
    </head>

    <body>
        <div class="wrapper">
            <nav id="sidebar" class="sidebar js-sidebar">
                <div class="sidebar-content js-simplebar">
                    <a class="sidebar-brand" href="{{ route('dashboard') }}">
                        <img src="{{ asset('img/conta-em-dia-logo.svg') }}" alt="" class="sidebar-brand-logo">
                        <span class="align-middle">Conta em dia</span>
                    </a>

                    <ul class="sidebar-nav">
                        <li class="sidebar-header">
                            Páginas
                        </li>

                        <li class="sidebar-item {{ Request::is('dashboard') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('dashboard') }}">
                                <i class="align-middle fas fa-chart-line"></i> <span class="align-middle">Dashboard</span>
                            </a>
                        </li>

                        <li class="sidebar-item {{ Request::is('perfil*') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('perfil.edit') }}">
                                <i class="align-middle fas fa-user"></i> <span class="align-middle">Perfil</span>
                            </a>
                        </li>

                        <li class="sidebar-header">
                            Finanças
                        </li>

                        <li class="sidebar-item {{ Request::is('entrada-salario*') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('entrada_salario.index') }}">
                                <i class="align-middle fas fa-chart-area"></i> <span class="align-middle">Entrada de Salário</span>
                            </a>
                        </li>

                        <li class="sidebar-item {{ Request::is('lancamentos*') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('lancamento.index') }}">
                                <i class="align-middle fas fa-chart-line"></i> <span class="align-middle">Lançamentos</span>
                            </a>
                        </li>

                        <li class="sidebar-item {{ Request::is('orcamentos*') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('orcamento.index') }}">
                                <i class="align-middle fas fa-bullseye"></i> <span class="align-middle">Orçamentos</span>
                            </a>
                        </li>

                        <li class="sidebar-header">
                            Controle
                        </li>

                        <li class="sidebar-item {{ Request::is('relatorios*') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('relatorio.index') }}">
                                <i class="align-middle fas fa-chart-bar"></i> <span class="align-middle">Relatórios</span>
                            </a>
                        </li>

                        <li class="sidebar-item {{ Request::is('auditoria*') ? 'active' : '' }}">
                                <a class="sidebar-link" href="{{ route('auditoria.index') }}">
                                <i class="align-middle fas fa-clipboard-check"></i> <span class="align-middle">Auditoria</span>
                            </a>
                        </li>

                        <li class="sidebar-header">
                            Configurações
                        </li>

                        <li class="sidebar-item {{ Request::is('configuracao/categoria') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('configuracao.categoria.index') }}">
                                <i class="align-middle fas fa-tags"></i> <span class="align-middle">Categorias</span>
                            </a>
                        </li>

                        <li class="sidebar-item {{ Request::is('configuracao/usuario') ? 'active' : '' }}">
                            <a class="sidebar-link" href="{{ route('configuracao.usuario.index') }}">
                                <i class="align-middle fas fa-users"></i> <span class="align-middle">Usuários</span>
                            </a>
                        </li>

                    </ul>
                </div>
            </nav>

            <div class="main">
                <nav class="navbar navbar-expand navbar-light navbar-bg">
                    <a class="sidebar-toggle js-sidebar-toggle">
                        <i class="hamburger align-self-center"></i>
                    </a>

                    <div class="navbar-collapse collapse">
                        <ul class="navbar-nav navbar-align">
                            <li class="nav-item currency-quote-item">
                                <div id="cotacao-dolar" class="currency-quote" data-url="{{ route('cotacao.dolar') }}"
                                    role="status" aria-live="polite" title="Cotação comercial do dólar via AwesomeAPI">
                                    <i class="fas fa-dollar-sign currency-quote-icon" aria-hidden="true"></i>
                                    <span class="currency-quote-content">
                                        <span class="currency-quote-label">Dólar comercial</span>
                                        <strong id="cotacao-dolar-valor">Carregando...</strong>
                                        <small id="cotacao-dolar-detalhe">Atualização automática</small>
                                    </span>
                                </div>
                            </li>
                            <li class="nav-item currency-quote-item">
                                <div class="currency-quote currency-quote-minimum"
                                    title="Salário mínimo nacional vigente em {{ config('services.salario_minimo.ano_vigencia') }}">
                                    <i class="fas fa-coins currency-quote-icon" aria-hidden="true"></i>
                                    <span class="currency-quote-content">
                                        <span class="currency-quote-label">Salário mínimo · {{ config('services.salario_minimo.ano_vigencia') }}</span>
                                        <strong>R$ {{ number_format(config('services.salario_minimo.valor'), 2, ',', '.') }}</strong>
                                        <small>Valor nacional vigente</small>
                                    </span>
                                </div>
                            </li>
                            <li class="nav-item topbar-theme-item">
                                <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Aplicar tema escuro"
                                    title="Aplicar tema escuro">
                                    <i class="fas fa-moon"></i>
                                </button>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-toggle="dropdown">
                                    <i class="align-middle" data-feather="settings"></i>
                                </a>

                                <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-toggle="dropdown">
                                    <span class="text-dark">{{ Auth::user()->name }} - {{ Auth::user()->email }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">

                                    {{-- <a class="dropdown-item" href="#">Log out</a> --}}
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Sair
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>

                <main class="content">
                    @yield('content')
                </main>

                <footer class="footer">
                    <div class="container-fluid">
                        <div class="row text-muted">
                            <div class="col-6 text-start">
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        {{-- Bootstrap 4.5 — inclui jQuery plugin .modal(), data-toggle, data-dismiss nativos --}}
        <script src="{{ url('js/bootstrap.js') }}"></script>

        <script src="{{asset('jquery-mask/dist/jquery.mask.min.js')}}"></script>
        <script src="{{ url('js/fontawesome.js') }}"></script>
        <script src="{{ url('js/functions.js') }}"></script>
        <script src="{{ url('js/prevent_multiple_submits.js') }}"></script>
        <script>
            (function () {
                var cotacao = document.getElementById('cotacao-dolar');

                if (!cotacao) {
                    return;
                }

                var valor = document.getElementById('cotacao-dolar-valor');
                var detalhe = document.getElementById('cotacao-dolar-detalhe');
                var formatoMoeda = new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
                var atualizando = false;

                function atualizarCotacao() {
                    if (atualizando || document.hidden) {
                        return;
                    }

                    atualizando = true;

                    fetch(cotacao.dataset.url, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(function (resposta) {
                            if (!resposta.ok) {
                                throw new Error('Não foi possível consultar a cotação.');
                            }

                            return resposta.json();
                        })
                        .then(function (dados) {
                            var compra = Number(dados.compra);
                            var venda = Number(dados.venda);
                            var variacao = Number(dados.variacao);
                            var dataAtualizacao = new Date(Number(dados.atualizadoEm) * 1000);

                            if (!Number.isFinite(compra) || !Number.isFinite(venda) || !Number.isFinite(variacao) || Number.isNaN(dataAtualizacao.getTime())) {
                                throw new Error('A cotação recebida é inválida.');
                            }

                            valor.textContent = formatoMoeda.format(compra);
                            detalhe.textContent = 'Venda ' + formatoMoeda.format(venda) + ' · ' + (variacao > 0 ? '+' : '') + variacao.toFixed(2).replace('.', ',') + '%';
                            cotacao.title = 'Atualizada em ' + new Intl.DateTimeFormat('pt-BR', {
                                dateStyle: 'short',
                                timeStyle: 'short'
                            }).format(dataAtualizacao) + ' · Fonte: AwesomeAPI';
                            cotacao.classList.remove('currency-quote-unavailable');
                        })
                        .catch(function () {
                            valor.textContent = 'Indisponível';
                            detalhe.textContent = 'Tentaremos atualizar novamente';
                            cotacao.classList.add('currency-quote-unavailable');
                        })
                        .finally(function () {
                            atualizando = false;
                        });
                }

                atualizarCotacao();
                window.setInterval(atualizarCotacao, 60000);
                document.addEventListener('visibilitychange', atualizarCotacao);
            }());
        </script>
        <script>
            (function () {
                var html = document.documentElement;
                var toggle = document.getElementById('theme-toggle');

                if (!toggle) {
                    return;
                }

                function atualizarBotaoTema() {
                    var temaEscuro = html.classList.contains('dark-theme');
                    var icone = toggle.querySelector('i');

                    icone.className = temaEscuro ? 'fas fa-sun' : 'fas fa-moon';
                    toggle.setAttribute('aria-label', temaEscuro ? 'Aplicar tema claro' : 'Aplicar tema escuro');
                    toggle.setAttribute('title', temaEscuro ? 'Aplicar tema claro' : 'Aplicar tema escuro');
                }

                toggle.addEventListener('click', function () {
                    var temaEscuro = html.classList.toggle('dark-theme');

                    localStorage.setItem('controle-financas-theme', temaEscuro ? 'dark' : 'light');
                    atualizarBotaoTema();
                });

                atualizarBotaoTema();
            }());
        </script>
        <script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.11.0/r-2.2.9/datatables.min.js"></script>
        <script src="{{asset('select2-4.1.0/dist/js/select2.min.js')}}"></script>
        <script src="{{ asset('js/datatables.min.js') }}"></script>
        <script src="{{asset('jquery-mask/src/jquery.mask.js')}}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8-beta.17/inputmask.js" integrity="sha512-XvlcvEjR+D9tC5f13RZvNMvRrbKLyie+LRLlYz1TvTUwR1ff19aIQ0+JwK4E6DCbXm715DQiGbpNSkAAPGpd5w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        @yield('scripts')
    </body>
</html>
