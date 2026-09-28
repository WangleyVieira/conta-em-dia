<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Lancamento extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'lancamentos';

    protected $fillable = [
        'tipo', 'competencia', 'descricao', 'valor', 'valor_pago', 'data_vencimento', 'is_receber', 'is_pago', 'data_pagamento',
        'observacao', 'link_pagamento', 'is_parcelado', 'parcela_atual', 'total_parcelas', 'valor_parcela', 'is_fixo', 'cadastrado_por_usuario',
        'categoria_id'
    ];

    protected function casts(): array
    {
        return [
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
            'valor' => 'decimal:2',
            'valor_pago' => 'decimal:2',
            'is_pago' => 'boolean',
            'is_parcelado' => 'boolean',
            'is_fixo' => 'boolean',
        ];
    }

    public function getSituacaoAttribute(): string
    {
        if ($this->is_pago || ($this->valor_pago !== null && (float) $this->valor_pago >= (float) $this->valor)) {
            return 'pago';
        }

        return $this->data_vencimento && $this->data_vencimento->lt(today())
            ? 'vencido'
            : 'pendente';
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cadastrado_por_usuario');
    }

}
