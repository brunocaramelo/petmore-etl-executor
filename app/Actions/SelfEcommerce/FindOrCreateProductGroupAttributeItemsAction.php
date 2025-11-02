<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttributeItem;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeItemsAction
{
    public function execute(Collection $param, SelfEcommerceConsumer $consumer)
    {
        $uniqueString = $param['slug'];

        $slugAttribute = Str::slug($uniqueString);

        $findLocaly = ProductGroupAttributeItem::where('slug', $slugAttribute)->first();

        $countTableItems = ProductGroupAttributeItem::count();

        if ($findLocaly instanceof ProductGroupAttributeItem) {
            return [
                'id' => $findLocaly->id,
                'slug' => $findLocaly->slug,
                'name' => $findLocaly->name,
                'sort_order' => $findLocaly->sort_order,
                'group_attribute_id' => $findLocaly->group_attribute_id,
            ];
        }

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'slug' => $param['slug'],
                'name' => $param['name'],
                'sort_order' => $countTableItems,
                'group_attribute_id' => $param['group_attribute_id'],
            ],
            'consumerInstance' => $consumer,
        ]);

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'sort_order' => $createdLocaly->sort_order,
            'group_attribute_id' => $createdLocaly->attribute_set_id,
        ];
    }

    private function addAttributeInternal($params)
    {
        $createdExternal = $params['consumerInstance']->createAttibuteSetItem([
                "attribute" => [
                    "attribute_code" => $params['data']['slug'],
                    "frontend_input" => "text",
                    "default_frontend_label" => $params['data']['name'],
                    "is_required" => false,
                    "is_user_defined" => true,
                    "is_visible" => true,
                    "scope" => "global",
                    "entity_type_id" => 4
                ]
        ]);

        $params['consumerInstance']->attachAttibuteIntoGroupAttrSet([
            "attributeSetId" => $createdExternal['attribute_id'],
            "attributeGroupId" => $params['group_attribute_id'],
            "attributeCode" => $params['slug'],
            "sortOrder" => $params['sort_order']
        ]);

        return ProductGroupAttributeItem::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'group_attribute_id' => $params['data']['group_attribute_id'],
                'self_ecommerce_identify' => $createdExternal['attribute_id'],
        ]);
    }



}
