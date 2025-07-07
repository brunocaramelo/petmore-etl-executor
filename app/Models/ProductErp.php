<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use App\Models\ProductCentral;

use App\Traits\HasUuid;

class ProductErp extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_erps';
    protected $primaryKey = 'uuid';

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
        return $this->hasMany(ProductCentral::class, 'product_erp_id');
    }

}
