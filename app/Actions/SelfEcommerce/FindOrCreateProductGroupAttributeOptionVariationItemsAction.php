<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttributeItem;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeOptionVariationItemsAction
{
   public function execute(Collection $param, $options, SelfEcommerceConsumer $consumer)
    {
        $item = $param['item'];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $uniqueString = $item['label'];

        $slugAttribute = Str::slug($uniqueString, '_').$options['sufix'];

        $findLocaly = ProductGroupAttributeItem::where('slug', $slugAttribute)
                                                ->first();

        $countTableItems = ProductGroupAttributeItem::count();

        if ($findLocaly instanceof ProductGroupAttributeItem
            && $findLocaly->group_attribute_id == $options['group_attribute_id']
        ) {
            return [
                'id' => $findLocaly->id,
                'slug' => $findLocaly->slug,
                'name' => $findLocaly->name,
                'sort_order' => $findLocaly->sort_order,
                'group_attribute_id' => $findLocaly->group_attribute_id,
                'self_ecommerce_identify' => $findLocaly->self_ecommerce_identify,
                'options' => $findLocaly->options,
            ];
        }

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'has_founded' => ($findLocaly instanceof ProductGroupAttributeItem),
                'find_localy' => $findLocaly,
                'slug' => $slugAttribute,
                'name' => $item['label'],
                'option' => $param['option'],
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
            'options' => $createdLocaly->options,
        ];
    }

    private function addAttributeInternal($params)
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        if ($params['data']['has_founded']) {
            $createdExternal['attribute_id'] = $params['data']['find_localy']->id;

            $params['data']['find_localy']->options = $this->addNewOptionAndReturn(
                $params['data']['find_localy'],
                 [
                            'label' => $params['option']['label']
                        ],
                $params['consumerInstance'],
            );

            $params['data']['find_localy']->save();
        }

        if (!$params['data']['has_founded']) {
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

        \Log::info(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success', $createdExternal);

        $params['consumerInstance']->attachAttibuteIntoGroupAttrSet([
            "attributeSetId" => $params['data']['group_attribute_id'],
            "attributeGroupId" => $params['data']['group_attribute_subgroup_id'],
            "attributeCode" => $params['data']['slug'],
            "sortOrder" => (int) $params['data']['sort_order'] ?? 0,
        ]);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success');
        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        usleep(100);

        return ProductGroupAttributeItem::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'type' => 'option',
                'group_attribute_id' => $params['data']['group_attribute_id'],
                'self_ecommerce_identify' => $createdExternal['attribute_id'],
                "options" => [
                    "label" => $params['option']['label'],
                    "value" => 1,
                ]
        ]);
    }

    private function addNewOptionAndReturn($attributte , array $option, $consumer)
    {
        $listOptions = $attributte->options ?? [];

        $option['value'] = (count($listOptions) + 1);

        if (array_filter($option['label'], fn($item) => ($item['label'] ?? null) === $option['label'])) {
            return $listOptions;
        }

        $consumer->attachAttibuteIntoGroupAttrSet($attributte->slug, $option);

        $listOptions[] = $option;

        return $listOptions;
    }

}
