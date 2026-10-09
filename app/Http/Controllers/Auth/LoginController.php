<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\Audit;
use RealRashid\SweetAlert\Facades\Alert;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function autenticacao(Request $request)
    {
        // 1) Valida os campos obrigatórios antes de tentar autenticar.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Chave única por e-mail + IP para controle de tentativas.
        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        // 2) Bloqueia temporariamente após muitas tentativas falhas.
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => "Muitas tentativas. Tente novamente em {$seconds}s."]);
        }

        // 3) Tenta autenticar com opção "lembrar-me" do formulário.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Audit::create([
                'action' => 'login_failed',
                'auditable_type' => 'auth',
                'new_values' => ['email' => $credentials['email']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            // Registra falha para aplicar throttling progressivo.
            RateLimiter::hit($throttleKey, 60);
            // return back()
            //     ->withInput($request->only('email', 'remember'))
            //     ->withErrors(['email' => 'E-mail ou senha inválidos.']);
            Alert::toast('E-mail ou senha inválidos.','error');
            return redirect()->back();
        }

        // 4) Login ok: limpa contador de falhas e regenera sessão por segurança.
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        Audit::create([
            'user_id' => Auth::id(),
            'action' => 'login',
            'auditable_type' => 'auth',
            'new_values' => ['email' => Auth::user()->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        Alert::toast('Login realizado com sucesso.', 'success');

        // Redireciona para página pretendida (ou dashboard, se não houver).
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Audit::create([
            'user_id' => $user?->id,
            'action' => 'logout',
            'auditable_type' => 'auth',
            'new_values' => ['email' => $user?->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        // Encerra autenticação do usuário atual.
        Auth::logout();

        // Invalida sessão e gera novo token CSRF por segurança.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Alert::toast('Sessão encerrada.', 'success');

        return redirect()->route('login');
    }
}
