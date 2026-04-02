<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttributeItem;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeTextItemsAction
{
    public function execute(Collection $param, $options, SelfEcommerceConsumer $consumer)
    {
        $item = $param['item'];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

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

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') toSend');

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'has_founded' => ($findLocaly instanceof ProductGroupAttributeItem),
                'find_localy' => $findLocaly,
                'slug' => $slugAttribute,
                'name' => $item['label'],
                'sort_order' => $countTableItems,
                'group_attribute_id' => $options['group_attribute_id'],
                'group_attribute_subgroup_id' => $options['group_attribute_subgroup_id'],
            ],
            'consumerInstance' => $consumer,
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

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
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $findLocalyJustSlug = ProductGroupAttributeItem::where('slug', $params['data']['slug'])
                                                ->first();

        if ($params['data']['has_founded']) {
            $createdExternal['attribute_id'] = $params['data']['find_localy']->self_ecommerce_identify;
        }

        if ($findLocalyJustSlug) {
            $createdExternal['attribute_id'] = $findLocalyJustSlug->self_ecommerce_identify;
            $params['data']['has_founded'] = true;
        }


        if (!$params['data']['has_founded']) {
            \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem to send',[
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

            usleep(100);
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success', $createdExternal);
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') attachAttibuteIntoGroupAttrSet to send', [
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

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success');
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        usleep(100);

        return ProductGroupAttributeItem::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'type' => 'text',
                'group_attribute_id' => $params['data']['group_attribute_id'],
                'self_ecommerce_identify' => $createdExternal['attribute_id'],
        ]);
    }

}
