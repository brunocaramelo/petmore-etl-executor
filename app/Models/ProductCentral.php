<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\{ProductErp,
                ProductMl,
                ProductRewrited,
                ProductCategory
                };

use App\Traits\HasUuid;

class ProductCentral extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_centrals';
    protected $primaryKey = 'uuid';

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
        'url_product_ml_original',
        'product_erp_id',
        'product_ml_id',
        'product_rewrited_id',
        'ml_identify',
        'category_id',
        'synced_self_ecommerce',
        'sku',
    ];

    protected $casts = [
        'specifications' => 'array',
        'variations' => 'array',
    ];

    public function productErp()
    {
        return $this->belongsTo(ProductErp::class, 'product_erp_id', 'uuid');
    }

    public function productMl()
    {
        return $this->belongsTo(ProductMl::class, 'product_ml_id',  'uuid');
    }

    public function productRewrited()
    {
        return $this->belongsTo(ProductRewrited::class, 'product_rewrited_id','uuid');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id','uuid');
    }

}
