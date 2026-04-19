<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;

use App\Traits\HasUuid;

class ProductSelfCommerceData extends Model
{
    use HasUuid;

    protected $hidden = ['_id'];
    protected $collection = 'product_self_commerce_data';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'URL',
        'NAME',
        'TYPE',
        'sku',
        'ean',
        'weight',
        'height',
        'width',
        'depth',
        'length',
        'has_variation',
        'parent_sku',
        'entity_id',
        'has_searched',
        'official_data_sended',
        'external_supplier_product_description',
        'external_supplier_name',
        'external_supplier_sku',
        'ecommerce_supplier_brand',
        'ecommerce_supplier_factory',
        'supplier_name',
        'supplier_preco_padrao',
        'supplier_desconto_percentual',
        'supplier_valor_final',
        'seller_sugestao_venda',
        'seller_markup',
    ];

    public function productCentral()
    {
        return $this->hasMany(ProductCentral::class, 'product_self_commerce_id');
    }

}
