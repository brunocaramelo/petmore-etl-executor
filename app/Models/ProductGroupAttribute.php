<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Traits\HasUuid;

class ProductGroupAttribute extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_group_attributes';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'id',
        'slug',
        'breadcrumb',
        'name',
        'sort_order',
        'self_ecommerce_identify',
        'self_ecommerce_group_fields',
    ];

    protected $casts = [
        'breadcrumb' => 'array',
        'self_ecommerce_group_fields' => 'array',
    ];


}
