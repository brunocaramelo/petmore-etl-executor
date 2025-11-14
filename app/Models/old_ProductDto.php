<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;

use App\Traits\HasUuid;

class OldProductDto extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_temps';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'id',
        'url',
        'title',
        'price',
        'sku',
        'attribute_set_id',
        'description',
        'specifications',
        'images',
        'variations',
        'metadata',
    ];

    protected $casts = [
        'specifications' => 'array',
    ];


}
