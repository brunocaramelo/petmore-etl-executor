<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use App\Models\ProductCentral;
use Illuminate\Database\Eloquent\Collection;

use App\Traits\HasUuid;

class ProductCategory extends Model
{
    use HasUuid;
    protected $hidden = ['_id'];
    protected $collection = 'product_categories';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'slug',
        'hierarquie',
        'name',
        'bling_identify',
        'bling_parent_identify',
        'parent_id',
    ];

    protected $casts = [
        'hierarquie' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id',  'uuid');
    }

    public function products()
    {
        return $this->belongsToMany(ProductCentral::class, 'category_id',  'uuid');
    }

    public function getAllParents(): Collection
    {
        $parents = new Collection();
        $current = $this;

        while ($current->parent) {
            $parents->prepend($current->parent);
            $current = $current->parent;
        }

        return $parents;
    }

    public function getFullHierarchy(): Collection
    {
        $hierarchy = $this->getAllParents();
        $hierarchy->push($this);

        return $hierarchy;
    }

}
