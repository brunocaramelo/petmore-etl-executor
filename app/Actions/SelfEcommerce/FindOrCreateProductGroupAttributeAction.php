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
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $uniqueString = $param['slug'];

        $slugAttribute = Str::slug($uniqueString, '_');

        $findLocaly = ProductGroupAttribute::where('slug', $slugAttribute)->first();

        $tableCounter = ProductGroupAttribute::count();

        if ($findLocaly instanceof ProductGroupAttribute) {

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') $findLocaly founded');
            \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAndGetGroupOfAttributeSetByName to execute');

            $foundedGroup = $this->createAndGetGroupOfAttributeSetByName($findLocaly,
            [
                        'group_attribute_name' => $param['group_attribute_name'],
                        'attribute_set_id' => $findLocaly->self_ecommerce_identify,
                    ],
                    $consumer);

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAndGetGroupOfAttributeSetByName to executed');
            \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished');

            return [
                'id' => $findLocaly->id,
                'slug' => $findLocaly->slug,
                'name' => $findLocaly->name,
                'sort_order' => $findLocaly->sort_order,
                'self_ecommerce_identify' => $findLocaly->self_ecommerce_identify,
                'breadcrumb' => $findLocaly->breadcrumb,
                'self_ecommerce_group_fields' => $foundedGroup,
            ];
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') addAttributeInternal to execute');

        $createdLocaly = $this->addAttributeInternal([
            'data' => [
                'slug' => $slugAttribute,
                'name' => $slugAttribute,
                'breadcrumb' => $param['breadcrumb'],
                'sort_order' => $tableCounter,
                'group_attribute_name' => $param['group_attribute_name'],
            ],
            'consumerInstance' => $consumer,
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') addAttributeInternal to executed');
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished');

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'sort_order' => (int) $createdLocaly->sort_order ?? 0,
            'self_ecommerce_identify' => $createdLocaly->attribute_set_id,
            'self_ecommerce_group_fields' => $createdLocaly->attribute_set_id,
        ];
    }

    private function addAttributeInternal($params)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $skeletonId = 4;
        $entityTypeId = 4;

        $createdExternal = $params['consumerInstance']->createAttibuteSet([
                "attributeSet" => [
                    "attribute_set_name" => $params['data']['slug'],
                    "sort_order" => $params['data']['sort_order'] ?? 50,
                    "entity_type_id" => $entityTypeId,
                ],
                "skeletonId" =>  $skeletonId,
                "entityTypeCode" => "catalog_product",
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        $createdInstance = ProductGroupAttribute::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'sort_order' => $params['data']['sort_order'],
                'self_ecommerce_identify' => $createdExternal['attribute_set_id'],
        ]);

        $foundedGroup = $this->createAndGetGroupOfAttributeSetByName($createdInstance,
                    [
                                'group_attribute_name' => $params['data']['group_attribute_name'],
                                'attribute_set_id' => $createdExternal['attribute_set_id'],
                            ],
                            $params['consumerInstance']
        );

        usleep(rand(40, 90));

        $createdInstance->self_ecommerce_group_fields = $foundedGroup;

        return $createdInstance;
    }

    private function createAndGetGroupOfAttributeSetByName($productGroupAttrInstance, $params, $consumer)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAndGetGroupOfAttributeSetByName to execute');

        $currentGroups = $productGroupAttrInstance->self_ecommerce_group_fields ?? [];

        $filtered = array_filter($currentGroups, fn($item) => $item['name'] === $params['group_attribute_name']);
        $foundGroup = $filtered ? reset($filtered) : null;

        if (!$foundGroup) {

            $createdExternalGroup = $consumer->getGroupsFromAttributeSet(
                    $params['attribute_set_id'],
                    $params['group_attribute_name']
            )[0] ?? [];

            if (empty($createdExternalGroup['attribute_group_id'])) {
                $createdExternalGroup = $consumer->addGroupAttibuteIntoAttributeSet([
                    "group" => [
                        "attribute_group_name" => $params['group_attribute_name'],
                        "attribute_set_id" => $params['attribute_set_id'],
                    ]
                ]);
            }

            $newGroup = [
                'id' => $createdExternalGroup['attribute_group_id'],
                'name' => $params['group_attribute_name'],
            ];

            $currentGroups[] = $newGroup;
            $foundGroup = $newGroup;
        }

        usleep(rand(86, 113));


        $productGroupAttrInstance->self_ecommerce_group_fields = $currentGroups;

        $productGroupAttrInstance->save();

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') createAndGetGroupOfAttributeSetByName executed');
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished');

        return $foundGroup;
    }



}
