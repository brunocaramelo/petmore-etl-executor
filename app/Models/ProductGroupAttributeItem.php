<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Traits\HasUuid;

class ProductGroupAttributeItem extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_group_attributes_items';
    protected $primaryKey = 'uuid';

    protected $casts = [
        'options' => 'array',
    ];

    protected $fillable = [
        'id',
        'slug',
        'name',
        'type',
        'sort_order',
        'options',
        'group_attribute_id',
        'self_ecommerce_identify',
    ];

}
