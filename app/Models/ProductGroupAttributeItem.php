<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Traits\HasUuid;

class ProductGroupAttributeItem extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_group_attributes_item';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'id',
        'slug',
        'name',
        'type',
        'product_group_attribute_id',
        'self_ecommerce_id',
    ];

}
