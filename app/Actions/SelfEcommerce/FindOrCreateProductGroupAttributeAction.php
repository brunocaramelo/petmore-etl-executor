<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttribute;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeAction
{
    public function execute(Collection $param, SelfEcommerceConsumer $consumer)
    {
        $uniqueString = $param['slug'];

        $slugAttribute = Str::slug($uniqueString);

        $findLocaly = ProductGroupAttribute::where('slug', $slugAttribute)->first();

        if ($findLocaly instanceof ProductGroupAttribute) {
            return [
                'id' => $findLocaly->id,
                'slug' => $findLocaly->slug,
                'name' => $findLocaly->name,
                'self_ecommerce_id' => $findLocaly->self_ecommerce_id,
                'breadcrumb' => $findLocaly->breadcrumb,
            ];
        }

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'slug' => $slugAttribute,
                'name' => $slugAttribute,
                'breadcrumb' => $param['breadcrumb'],
            ],
            'consumerInstance' => $consumer,
        ]);

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'self_ecommerce_identify' => $createdLocaly->attribute_set_id,
        ];

    }

    private function addAttributeInternal($params)
    {
        $createdExternal = $params['consumerInstance']->createAttibuteSet([
                "attributeSet" => [
                    "attribute_set_name" => $params['data']['slug'],
                    "sort_order" => $params['data']['sort_order'] ?? 50,
                    "entity_type_id" => 4,
                    "skeleton_id" => 4,
            ]
        ]);


        return ProductGroupAttribute::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'self_ecommerce_identify' => $createdExternal['attribute_set_id'],
        ]);
    }



}
