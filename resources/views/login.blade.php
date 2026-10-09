<!DOCTYPE html>
<html lang="pt-BR">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Controle-Finanças - Gerencie suas finanças com inteligência">
        <meta name="author" content="Controle-Finanças">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="icon" type="image/svg+xml" href="{{ asset('img/conta-em-dia-logo.svg') }}">

        <title>Entrar | Conta em dia</title>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">

    </head>

    <body>
        <div class="login-wrapper">

            <!-- ══ LEFT: BANNER PANEL ══ -->
            <div class="banner-panel">
                <div class="deco-circle deco-circle-1"></div>
                <div class="deco-circle deco-circle-2"></div>

                <img src="{{ asset('img/finance-banner.png') }}" alt="Painel financeiro" class="banner-bg-image">

                <div class="banner-overlay">
                    <!-- Logo -->
                    <div class="banner-logo">
                        <img src="{{ asset('img/conta-em-dia-logo.svg') }}" alt="" class="banner-logo-icon">
                        <div class="banner-logo-text">Conta em<span> dia</span></div>
                    </div>

                    <!-- Headline -->
                    <h2 class="banner-headline">
                        Gerencie suas<br>
                        <span>finanças com<br>inteligência</span>
                    </h2>

                    <p class="banner-sub">
                        Acompanhe receitas, despesas e investimentos em um só lugar.
                        Tome decisões mais inteligentes com dados em tempo real.
                    </p>

                    <!-- Stat chips -->
                    <div class="banner-stats">
                        <div class="stat-chip">
                            <div class="stat-chip-icon gold">📈</div>
                            <div class="stat-chip-info">
                                <small>Controle Total</small>
                                <strong>Receitas & Despesas</strong>
                            </div>
                        </div>
                        <div class="stat-chip">
                            <div class="stat-chip-icon green">✅</div>
                            <div class="stat-chip-info">
                                <small>Relatórios</small>
                                <strong>Dashboards em tempo real</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ RIGHT: FORM PANEL ══ -->
            @include('sweetalert::alert')
            <div class="form-panel">
                <div class="form-header">
                    <span class="badge-top">🔐 Acesso seguro</span>
                    <h1>Bem-vindo de volta!</h1>
                    <p>Entre com suas credenciais para acessar o sistema.</p>
                </div>

                @if(session('error') || $errors->any())
                    <div class="alert-box">
                        {{ session('error') ?? $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.autenticacao') }}" class="login-form">
                    @csrf
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <div class="input-wrapper">
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="seu@email.com"
                                autocomplete="email"
                                required
                            >
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="input-wrapper">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="form-footer-row">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" id="remember-me">
                            Lembrar-me
                        </label>
                        <a href="#" class="forgot-link">Esqueci a senha</a>
                    </div>

                    <button type="submit" class="btn-login" id="btn-entrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0v-2z"/>
                            <path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                        </svg>
                        Entrar no sistema
                    </button>
                </form>

                <div class="divider">ou</div>
                <div class="form-footer-note">
                    Não tem uma conta? <a href="#">Cadastre-se gratuitamente</a>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/app.js') }}"></script>
    </body>

</html>
