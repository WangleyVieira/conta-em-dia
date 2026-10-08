<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CotacaoDolarController;
use App\Http\Controllers\EntradaSalarioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/autenticacao', [LoginController::class, 'autenticacao'])->name('login.autenticacao');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorio.index');
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil/{id}', [PerfilController::class, 'update'])->name('perfil.update');

    Route::group(['prefix' => '/lancamentos', 'as' => 'lancamento.'], function() {
        Route::get('/', [LancamentoController::class, 'index'])->name('index');
        Route::get('/create', [LancamentoController::class, 'create'])->name('create');
        Route::post('/store', [LancamentoController::class, 'store'])->name('store');
        Route::put('/update/{id}', [LancamentoController::class, 'update'])->name('update');
        Route::get('/edit/{id}', [LancamentoController::class, 'edit'])->name('edit');
        Route::post('/{id}/gerar-proxima-competencia', [LancamentoController::class, 'gerarProximaCompetencia'])->name('gerar_proxima_competencia');
        Route::delete('/destroy/{id}', [LancamentoController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => '/entrada-salario', 'as' => 'entrada_salario.'], function() {
        Route::get('/', [EntradaSalarioController::class, 'index'])->name('index');
        Route::get('/create', [EntradaSalarioController::class, 'create'])->name('create');
        Route::post('/store', [EntradaSalarioController::class, 'store'])->name('store');
        Route::put('/update/{id}', [EntradaSalarioController::class, 'update'])->name('update');
        Route::get('/edit/{id}', [EntradaSalarioController::class, 'edit'])->name('edit');
        Route::delete('/destroy/{id}', [EntradaSalarioController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => '/configuracao', 'as' => 'configuracao.'], function() {

        Route::group(['prefix' => '/categoria', 'as' => 'categoria.'], function() {
            Route::get('/', [CategoriaController::class, 'index'])->name('index');
            Route::post('/store', [CategoriaController::class, 'store'])->name('store');
            Route::put('/update/{id}', [CategoriaController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [CategoriaController::class, 'destroy'])->name('destroy');
        });

        Route::group(['prefix' => '/usuario', 'as' => 'usuario.'], function() {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/store', [UserController::class, 'store'])->name('store');
            Route::put('/update/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->name('destroy');
        });

    });

});
