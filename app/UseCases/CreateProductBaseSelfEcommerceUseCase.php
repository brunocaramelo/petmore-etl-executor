<?php

namespace App\UseCases;

use App\Consumers\SelfEcommerceConsumer;

use App\Actions\SelfEcommerce\{FindOrCreateProductGroupAttributeAction,
                               FindOrCreateProductGroupAttributeTextItemsAction,
                               FindOrCreateProductGroupAttributeOptionVariationItemsAction};

use App\Models\{ProductRewrited,
                ProductCategory
                };

use App\UseCases\CreateProductChildSelfEcommerceUseCase;

use App\Jobs\{UploadImageJpgToSelfCommerceToProductJob,
             SendProductChidrenAndAttachParentJob}
;

use Illuminate\Support\Str;
use Carbon\Carbon;
class CreateProductBaseSelfEcommerceUseCase
{
    private $consumer;
    private $typeProduct;
    private $productnstance;

    public function __construct(SelfEcommerceConsumer $consumer,
                                ProductRewrited $productnstance)
    {
        $this->consumer = $consumer;
        $this->productnstance = $productnstance;
    }

    public function handle()
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $productVatiations = $this->productnstance->variations ?? [];
        $categoryAttrs = $this->productnstance?->productCentral()->first()->category()->first() ?? null;
        $categoryAttrsProductAttributesItems = $this->productnstance?->specifications ?? null;

        $attributeSetArr = $this->createAttributeSet([
            'slug' => $categoryAttrs->slug,
            'group_attribute_name' => 'Attributes',
            'breadcrumb' => $categoryAttrs->hierarquie,
        ]);

        $attributeSetAttributesList = $this->createAttributeSetAttributes([
            'group_attribute_id' => $attributeSetArr['self_ecommerce_identify'],
            'group_attribute_subgroup_id' => $attributeSetArr['self_ecommerce_group_fields']['id'],
            'items' => $categoryAttrsProductAttributesItems,
        ]);

        $this->typeProduct = (is_array($productVatiations) && !empty($productVatiations)
            ? 'configurable'
            : 'simple'
        );

        $this->productnstance->attribute_set_id = $attributeSetArr['self_ecommerce_identify'];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createProduct');

        $configurableProduct = $this->createProduct($this->productnstance);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createProduct');


        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createImagesIntoProduct');

        $returnProductImages = $this->createImagesIntoProduct($this->productnstance->sku, $this->productnstance->images ?? []);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createImagesIntoProduct');

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createVariationAsyncItems');



        $this->prepareAndcreateVariationItems($this->productnstance,
            collect($productVatiations)->map(fn($i) => (object)$i) ?? [],
            [
                'attributeSetData' => $attributeSetArr,
                'categoryAttrData' => $categoryAttrs,
        ]);


        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createVariationAsyncItems');

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $this->productnstance;
    }

    private function prepareAndcreateVariationItems($productParent, $childItems, $configsVariations)
    {
        foreach ($childItems as $variationItemAttr) {

            $attributeSetArr = $this->createAttributeSet([
            'slug' => $configsVariations['attributeSetData']['slug'],
            'group_attribute_name' => 'Variações',
            'breadcrumb' => $configsVariations['attributeSetData']['breadcrumb'],
            ]);

             \Log::info('variations values before' ,[
                'group_attribute_id' => $attributeSetArr['self_ecommerce_identify'],
                'group_attribute_subgroup_id' => $attributeSetArr['self_ecommerce_group_fields']['id'],
                'item' => $variationItemAttr->attributes
            ]);

            $getListAttributesForVariationOption = $this->createAttributeSetAttributesVariations([
                'group_attribute_id' => $attributeSetArr['self_ecommerce_identify'],
                'group_attribute_subgroup_id' => $attributeSetArr['self_ecommerce_group_fields']['id'],
                'item' => $variationItemAttr->attributes
            ]);



            \Log::info('variations values All' ,[
            'attributeSetArr' => $attributeSetArr,
            'getListAttributesForVariationOption' => $getListAttributesForVariationOption,
            ]);

        }

        die();

        foreach ($childItems as $indexVariation => $variationItem) {

            $configsVariations['last_run']->addSeconds(rand(37, 70));

            $this->createVariationItem(
            $this->productnstance,
                [
                    'group_attribute_id' => $configsVariations['attributeSetData']['self_ecommerce_identify'],
                    'group_attribute_subgroup_id' => $configsVariations['attributeSetData']['self_ecommerce_group_fields']['id'],
                    'index_variation' => $indexVariation,
                    'attribute_set_slug' => $configsVariations['categoryAttrData']->slug,
                    'attribute_set_group_attribute_name' => 'Variações',
                    'attribute_set_breadcrumb' => $configsVariations['categoryAttrData']->hierarquie,
                ],
                $variationItem,
                $configsVariations['last_run']
            );
        }


    }

    private function createVariationItem($productParent, $auxArr , $childItem, $lastCarbonInstance)
    {


        SendProductChidrenAndAttachParentJob::dispatch(
            $childItem
            ,$productParent
            ,[
                'last_carbon_time_dispatch' => $lastCarbonInstance
            ]
        )->delay($lastCarbonInstance);

    }


    private function getCategoriesHierarquies($uuId)
    {
        return  ProductCategory::where('uuid', $uuId)
                                ->first()
                                ->getFullHierarchy();

    }

    private function createAttributeSet(array $params): array
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        return  (new FindOrCreateProductGroupAttributeAction)
                ->execute(collect([
                    'slug' => $params['slug'],
                    'breadcrumb' => $params['breadcrumb'],
                    'group_attribute_name' => $params['group_attribute_name'],
                ]), $this->consumer);
    }

    private function createAttributeSetAttributesVariations(array $params): array
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = [];
        foreach ($params as $itemAttr) {
            $returnData[] = (new FindOrCreateProductGroupAttributeOptionVariationItemsAction)
                    ->execute(collect([
                        'group_attribute_id' => $params['group_attribute_id'],
                        'item' => $itemAttr[0]['label'],
                        'option' => ['lable' => $itemAttr[1]['value']],
                ]),
                [
                        'group_attribute_subgroup_id' => $params['group_attribute_subgroup_id'],
                        'group_attribute_id' => $params['group_attribute_id'],
                        'sufix' => '_option',
                ],
                 $this->consumer)['self_ecommerce_identify'];
            }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function createAttributeSetAttributes(array $params): array
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = [];
        foreach ($params['items'] as $itemAttrItems) {
            foreach ($itemAttrItems['rows'] as $itemAttr) {

            $returnData[] = (new FindOrCreateProductGroupAttributeTextItemsAction)
                    ->execute(collect([
                        'group_attribute_id' => $params['group_attribute_id'],
                        'item' => $itemAttr,
                ]),
           [
                        'group_attribute_subgroup_id' => $params['group_attribute_subgroup_id'],
                        'group_attribute_id' => $params['group_attribute_id'],
                        'sufix' => '_text',
                    ],
                 $this->consumer)['self_ecommerce_identify'];
            }
        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function getFormatedCustomAttributesList($params): array
    {
        $returnData = [];
        foreach ($params['items'] as $itemAttrItems) {
            foreach ($itemAttrItems['rows'] as $itemAttr) {
                $returnData[] = [
                    'attribute_code' => Str::slug($itemAttr['label'], '_').$params['sufix'],
                    'value' => $itemAttr['value'],
                ];
            }
        }

        $returnData[] = [
            "attribute_code" => "description",
            "value" => $this->productnstance->productCentral()
                            ->first()
                            ->description['small']['complement'] ?? "description",
        ];

        $returnData[] = [
            "attribute_code" => "short_description",
            "value" => $this->productnstance->productCentral()
                            ->first()
                            ->description['small']['html'] ?? "short_description",
        ];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function createProduct(ProductRewrited $productData): ?array
    {
        $listCategories = $this->getCategoriesHierarquies($productData->productCentral()->first()->category_id);

        $extensionAttributes = [
            'stock_item' => [
                'qty' => 0,
                'is_in_stock' => true,
                'manage_stock' => true,
                'use_config_manage_stock' => false,
                'min_qty' => 0,
                'use_config_min_qty' => false,
                'min_sale_qty' => 1,
                'max_sale_qty' => 100,
                'use_config_max_sale_qty' => false,
                'is_qty_decimal' => false,
                'backorders' => 0,
                'use_config_backorders' => false,
                'notify_stock_qty' => 0,
                'use_config_notify_stock_qty' => false
                ],
            ];

        if ($listCategories->count() > 0) {
            $extensionAttributes['category_links'] = $listCategories->map(function ($category, $index) {
                return [
                    'position' => $index,
                    'category_id' => $category->self_ecommerce_id ?? 'none',
                ];
            })->values()->toArray();
        }

        $payload = [
            "product" => [
                "sku" => $productData->sku,
                "name" => $productData->title,
                "attribute_set_id" => $productData->attribute_set_id,
                "price" => $productData->price['current'],
                "status" => 1,
                "visibility" => 4,
                "type_id" => $this->typeProduct,
                "weight" => 1,
                "extension_attributes" => $extensionAttributes,
                "custom_attributes" =>  $this->getFormatedCustomAttributesList([
                    'items' => $this->productnstance?->specifications ?? [],
                    'sufix' => '_text'
                ]),
            ]
        ];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') prepare to send $this->consumer->createProduct', $payload);

        return $this->consumer->createProduct($payload);
    }

    private function createImagesIntoProduct($productSku, array $images): array
    {
        $delayToJob = Carbon::now();

        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        foreach ($images as $img) {

            $delayToJob->addSeconds(rand(15,40));

            UploadImageJpgToSelfCommerceToProductJob::dispatch(
                $productSku,
                $img['full_size'],
                    $this->consumer
            )->delay($delayToJob);

            \Log::info(__CLASS__.' ('.__FUNCTION__.') enviando item para UploadImageJpgToSelfCommerceToProductJob', [
              'productSku' => $productSku,
              'imageFull' => $img['full_size'],
              'runAt' => $delayToJob,
            ]);

        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finished');

        return [
            'last_run' => $delayToJob,
        ];
    }



}
