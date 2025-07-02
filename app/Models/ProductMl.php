<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;

class ProductMl extends Model
{
    protected $collection = 'product_mls';

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'tags',
        'details',
        'is_active'
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'tags' => 'array',
        'details' => 'array',
    ];

    public function productCentral()
    {
        return $this->belongsTo(ProductCentral::class, 'product_erp_id');
    }

}
