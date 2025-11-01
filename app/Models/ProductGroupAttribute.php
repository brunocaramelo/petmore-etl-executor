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
        'self_ecommerce_id'
    ];

    protected $casts = [
        'breadcrumb' => 'array',
    ];


}
