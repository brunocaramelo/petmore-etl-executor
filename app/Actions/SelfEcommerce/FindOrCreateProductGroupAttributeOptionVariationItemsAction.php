<?php

namespace App\Actions\SelfEcommerce;

use App\Models\ProductGroupAttributeItem;

use App\Consumers\SelfEcommerceConsumer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindOrCreateProductGroupAttributeOptionVariationItemsAction
{
   public function execute(Collection $param, $options, SelfEcommerceConsumer $consumer) : ProductGroupAttributeItem
    {
        $slugAttribute = Str::slug($param['item'], '_').$options['sufix'];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $findLocaly = ProductGroupAttributeItem::where('slug', $slugAttribute)
                                                ->where('group_attribute_id', $options['group_attribute_id'])
                                                ->first();

        $countTableItems = ProductGroupAttributeItem::count();

        if ($findLocaly instanceof ProductGroupAttributeItem) {

            if (!array_filter($findLocaly->options ?? [], fn($item) => ($item['label'] ?? null) === $param['option'])) {
                $findLocaly->options = $this->addNewOptionAndReturn(
                        $findLocaly,
                        $param['option'],
                        $consumer,
                    );

                $findLocaly->save();
            }

            return $findLocaly;
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') working 1.1');

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'has_founded' => ($findLocaly instanceof ProductGroupAttributeItem),
                'find_localy' => $findLocaly,
                'slug' => $slugAttribute,
                'name' => $param['item'],
                'option' => $param['option'],
                'sort_order' => $countTableItems,
                'group_attribute_id' => $options['group_attribute_id'],
                'group_attribute_subgroup_id' => $options['group_attribute_subgroup_id'],
            ],
            'consumerInstance' => $consumer,
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        return $createdLocaly;
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

            if (!array_filter($findLocalyJustSlug->options ?? [], fn($item) => ($item['label'] ?? null) === $params['data']['option'])) {
                $findLocalyJustSlug->options = $this->addNewOptionAndReturn(
                        $findLocalyJustSlug,
                        $params['data']['option'],
                        $params['consumerInstance'],
                    );
            }
        }

        if (!$params['data']['has_founded']) {
            \Log::debug(__CLASS__.' ('.__FUNCTION__.') not has_founded init',[
                'attribute' => [
                    'name' => $params['data']['name'],
                    'slug' => $params['data']['slug'],
                ],
                'option' => $params['data']['option']
            ]);

            $createdExternal = $params['consumerInstance']->createAttibuteSetItem([
                    "attribute" => [
                        "attribute_code" => $params['data']['slug'],
                        "frontend_input" => "select",
                        "default_frontend_label" => $params['data']['name'],
                        "is_required" => false,
                        "is_user_defined" => true,
                        "is_visible_on_front" => true,
                        "is_visible" => true,
                        "scope" => "global",
                        "entity_type_id" => 4
                    ]
            ]);

            usleep(rand(100, 300));

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') not has_founded finished', ['$createdExternal'=> $createdExternal]);
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem sended success');

        $params['consumerInstance']->attachAttibuteIntoGroupAttrSet([
            "attributeSetId" => $params['data']['group_attribute_id'],
            "attributeGroupId" => $params['data']['group_attribute_subgroup_id'],
            "attributeCode" => $params['data']['slug'],
            "sortOrder" => (int) $params['data']['sort_order'] ?? 0,
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAttibuteSetItem (attachAttibuteIntoGroupAttrSet) sended success', [
            "attributeSetId" => $params['data']['group_attribute_id'],
            "attributeGroupId" => $params['data']['group_attribute_subgroup_id'],
            "attributeCode" => $params['data']['slug'],
            "sortOrder" => (int) $params['data']['sort_order'] ?? 0,
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        usleep(rand(50, 210));

        $createdItem = ProductGroupAttributeItem::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'type' => 'option',
                'group_attribute_id' => $params['data']['group_attribute_id'],
                'self_ecommerce_identify' => $createdExternal['attribute_id'],
        ]);

        $createdItem->options = $this->addNewOptionAndReturn(
            $createdItem,
            $params['data']['option'],
            $params['consumerInstance'],
        );

        return $createdItem;
    }

    private function addNewOptionAndReturn($attributte , $optionLabel, $consumer) : array
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $allTributeOption = ProductGroupAttributeItem::where('slug', $attributte->slug)
                                                ->first();

        $listOptions = $allTributeOption->options ?? [];

        $option = [
            'label' => $optionLabel,
            'value' => (int) (count($listOptions) + 1)
        ];

        if (array_filter($listOptions, fn($item) => ($item['label'] ?? null) === $optionLabel)) {
            $this->updateOptionByAttSlug($allTributeOption->slug, $listOptions);
            return $listOptions;
        }

        $reponseApi = $consumer->attachOptionIntoAttibuteAttrSet($attributte->slug, [
            'option' => [
                'label' => $option['label'],
                'sort_order' => $option['value'],
                'is_default' => false,
                'store_labels' => [
                    [
                        'store_id' => 0,
                        'label' => $option['label']
                    ]
                ]
            ]
        ]);

        usleep(rand(80, 110));

        $option['value'] = $reponseApi;

        $listOptions[] = $option;

        $this->updateOptionByAttSlug($allTributeOption->slug, $listOptions);

        return $listOptions;
    }

    private function updateOptionByAttSlug($slug, $options)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        ProductGroupAttributeItem::where('slug', $slug)
                                ->update([
                                    'options' => json_encode($options)
                                ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');
    }

}

