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
    ];

    public function productCentral()
    {
        return $this->hasMany(ProductCentral::class, 'product_self_commerce_id');
    }

}
