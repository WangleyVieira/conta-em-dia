<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('categoria_id');
            $table->string('competencia', 7);
            $table->decimal('valor_limite', 12, 2);
            $table->foreign('categoria_id')->references('id')->on('categorias');
            $table->unique(['categoria_id', 'competencia']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
