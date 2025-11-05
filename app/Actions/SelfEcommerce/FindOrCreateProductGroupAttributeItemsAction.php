<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttributeItem;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeItemsAction
{
    public function execute(Collection $param, $options, SelfEcommerceConsumer $consumer)
    {
        $item = $param['item'];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $uniqueString = $item['label'];

        $slugAttribute = Str::slug($uniqueString, '_').$options['sufix'];

        $findLocaly = ProductGroupAttributeItem::where('slug', $slugAttribute)
                                                ->where('group_attribute_id', $options['group_attribute_id'])
                                                ->first();

        $countTableItems = ProductGroupAttributeItem::count();

        if ($findLocaly instanceof ProductGroupAttributeItem) {
            return [
                'id' => $findLocaly->id,
                'slug' => $findLocaly->slug,
                'name' => $findLocaly->name,
                'sort_order' => $findLocaly->sort_order,
                'group_attribute_id' => $findLocaly->group_attribute_id,
                'self_ecommerce_identify' => $findLocaly->self_ecommerce_identify,

            ];
        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') toSend' ,[
            'slug' => $slugAttribute,
            'name' => $item['label'],
            'sort_order' => $countTableItems,
            'group_attribute_id' => $options['group_attribute_id'],
        ]);

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'slug' => $slugAttribute,
                'name' => $item['label'],
                'sort_order' => $countTableItems,
                'group_attribute_id' => $options['group_attribute_id'],
                'group_attribute_subgroup_id' => $options['group_attribute_subgroup_id'],
            ],
            'consumerInstance' => $consumer,
        ]);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'sort_order' => $createdLocaly->sort_order,
            'group_attribute_id' => $createdLocaly->group_attribute_id,
            'self_ecommerce_identify' => $createdLocaly->self_ecommerce_identify,
        ];
    }

    private function addAttributeInternal($params)
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');
        \Log::info(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem to send',[
                "attribute" => [
                    "attribute_code" => $params['data']['slug'],
                    "frontend_input" => "text",
                    "default_frontend_label" => $params['data']['name'],
                    "is_required" => false,
                    "is_user_defined" => true,
                    "is_visible" => true,
                    "scope" => "store",
                    "entity_type_id" => 4
                ]
        ]);

        $createdExternal = $params['consumerInstance']->createAttibuteSetItem([
                "attribute" => [
                    "attribute_code" => $params['data']['slug'],
                    "frontend_input" => "text",
                    "default_frontend_label" => $params['data']['name'],
                    "is_required" => false,
                    "is_user_defined" => true,
                    "is_visible_on_front" => true,
                    "is_visible" => true,
                    "scope" => "store",
                    "entity_type_id" => 4
                ]
        ]);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success', $createdExternal);
        \Log::info(__CLASS__.' ('.__FUNCTION__.') attachAttibuteIntoGroupAttrSet to send', [
            "attributeSetId" => $params['data']['group_attribute_id'],
            "attributeGroupId" => $params['data']['group_attribute_subgroup_id'],
            "attributeCode" => $params['data']['slug'],
            "sortOrder" => (int) $params['data']['sort_order'],
        ]);


        $params['consumerInstance']->attachAttibuteIntoGroupAttrSet([
            "attributeSetId" => $params['data']['group_attribute_id'],
            "attributeGroupId" => $params['data']['group_attribute_subgroup_id'],
            "attributeCode" => $params['data']['slug'],
            "sortOrder" => (int) $params['data']['sort_order'] ?? 0,
        ]);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success');
        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return ProductGroupAttributeItem::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'group_attribute_id' => $params['data']['group_attribute_id'],
                'self_ecommerce_identify' => $createdExternal['attribute_id'],
        ]);
    }



}
