<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\{ProductErp,
                ProductMl
                };


class ProductCentral extends Model
{
    protected $collection = 'product_centrals';

    protected $fillable = [
        'ploutos_cod',
        'ploutos_cod_barras',
        'ploutos_descricao',
        'ploutos_categoria',
        'ploutos_marca',
        'ploutos_custo_tabela',
        'ploutos_estoque_minimo_ui',
        'ploutos_saldo_ui',
        'ploutos_estoque_atual_ui',
        'ploutos_unidade_de_medida',
        'ploutos_custo_medio_ui',
        'ploutos_preco_uso',
        'ploutos_estoque_minimo_rv',
        'ploutos_saldo_rv',
        'ploutos_estoque_atual_rv',
        'ploutos_custo_medio_rv',
        'ploutos_preco_venda',
        'is_active',
        'is_to_sell',
        'synced_erp',
        'synced_ml',
        'ai_adapted_the_content',
        'url_product_ml',
        'product_erp_id',
        'product_ml_id',
    ];

    protected $casts = [
    ];

    public function productErp()
    {
        return $this->hasOne(ProductErp::class, 'product_erp_id');
    }

    public function productMl()
    {
        return $this->hasOne(ProductMl::class, 'product_ml_id');
    }

}
