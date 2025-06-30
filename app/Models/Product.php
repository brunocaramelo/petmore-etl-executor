<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $collection = 'products_central';

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


}
