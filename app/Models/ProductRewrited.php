<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;

use App\Traits\HasUuid;

class ProductRewrited extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_rewriteds';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'id',
        'url',
        'title',
        'price',
        'sku',
        'description',
        'specifications',
        'images',
        'variations',
        'metadata',
    ];

    // protected $casts = [
    //     'price' => 'float',
    //     'is_active' => 'boolean',
    //     'tags' => 'array',
    //     'details' => 'array',
    // ];

    public function productCentral()
    {
        return $this->hasMany(ProductCentral::class, 'product_rewrited_id');
    }

}
