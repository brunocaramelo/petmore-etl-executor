<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;

use App\Traits\HasUuid;

class ProductCustomAttribute extends Model
{
    use HasUuid;

    protected $hidden = ['_id'];
    protected $collection = 'product_custom_attributes';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'slug',
        'name',
        'bling_identify',
        'bling_group_field',
    ];

    protected $casts = [
        'bling_group_field' => 'array',
    ];


}
