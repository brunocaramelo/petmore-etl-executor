<?php

namespace App\UseCases;

use App\Consumers\SelfEcommerceConsumer;

use App\Actions\SelfEcommerce\{FindOrCreateProductGroupAttributeAction,
                               FindOrCreateProductGroupAttributeTextItemsAction,
                               FindOrCreateProductGroupAttributeOptionVariationItemsAction};

use App\Models\{ProductRewrited,
                ProductCategory,
                ProductDto
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
    private $hasVariations = false;

    public function __construct(SelfEcommerceConsumer $consumer,
                                ProductRewrited $productnstance)
    {
        $this->consumer = $consumer;
        $this->productnstance = $productnstance;
    }

    public function handle()
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');


        if(empty($this->productnstance->title)) {
            $this->productnstance->title = $this->productnstance?->productCentral()->first()->ploutos_descricao;
        }

        if(empty($this->productnstance->price)) {
            $this->productnstance->price = 11;
        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') importing: ', [
            'sku' => $this->productnstance->sku,
            'title' => $this->productnstance->title,
        ]);

        $delayToJob = Carbon::now();

        $categoryAttrsProductAttributesItems = [];


        $productVatiations = $this->productnstance->variations ?? [];
        $productVatiationsAttributes = $productVatiations[0]['attributes'] ?? [];

        $categoryAttrs = $this->productnstance?->productCentral()->first()->category()->first() ?? null;

        $this->hasVariations = (count($productVatiations) > 0
                                && count($productVatiationsAttributes) > 0
                            );

        $categoryAttrsProductAttributesItems = $this->productnstance?->specifications ?? null;


        $attributeSetArr = $this->createAttributeSet([
            'slug' => $categoryAttrs->slug,
            'group_attribute_name' => 'Attributes',
            'breadcrumb' => $categoryAttrs->hierarquie,
        ]);

        $this->createAttributeSetAttributes([
            'group_attribute_id' => $attributeSetArr['self_ecommerce_identify'],
            'group_attribute_subgroup_id' => $attributeSetArr['self_ecommerce_group_fields']['id'],
            'items' => $categoryAttrsProductAttributesItems,
        ]);

        $this->typeProduct = ($this->hasVariations
            ? 'configurable'
            : 'simple'
        );

        $this->productnstance->attribute_set_id = $attributeSetArr['self_ecommerce_identify'];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createProduct');

        $configurableProduct = $this->createProduct($this->productnstance);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createProduct');


        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createImagesIntoProduct');

        $this->createImagesIntoProduct(
                                $this->productnstance->sku,
                                $this->productnstance->images ?? [],
                                $delayToJob);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createImagesIntoProduct');

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createVariationAsyncItems');


        if ($this->hasVariations) {
            $this->prepareAndcreateVariationItems($this->productnstance,
                $productVatiations,
                [
                    'attributeSetData' => $attributeSetArr,
                    'categoryAttrData' => $categoryAttrs,
                    'last_run' => $delayToJob,
            ]);
        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createVariationAsyncItems');

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $this->productnstance;
    }


    private function prepareAndcreateVariationItems($productParent, $childItems, $configsVariations)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $listOfAttrVariationsProduct = [];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') before createAttributeSet and createAttributeSetAttributesVariations in variations loop DEBUG:',[
            '$childItems[0]->attributes', $childItems[0]['attributes'],
        ]);

        foreach ($childItems as $variationItemAttr) {
            $attributeSetArr = $this->createAttributeSet([
                'slug' => $configsVariations['attributeSetData']['slug'],
                'group_attribute_name' => 'Variações',
                'breadcrumb' => $configsVariations['attributeSetData']['breadcrumb'],
            ]);

            foreach($variationItemAttr['attributes'] ?? [] as $itemAttrVariation) {
               $responseAttrVariationOptions = $this->createAttributeSetAttributesVariations([
                    'group_attribute_id' => $attributeSetArr['self_ecommerce_identify'],
                    'group_attribute_subgroup_id' => $attributeSetArr['self_ecommerce_group_fields']['id'],
                    'item' => $itemAttrVariation[0]['label'],
                    'option' => $itemAttrVariation[1]['value'],
                ]);

                $listOfAttrVariationsProduct[$responseAttrVariationOptions->uuid] = [
                    'id_local' => $responseAttrVariationOptions->uuid,
                    'slug' => $responseAttrVariationOptions->slug,
                    'name' => $responseAttrVariationOptions->name,
                    'type' => $responseAttrVariationOptions->type,
                    'group_attribute_id' => $responseAttrVariationOptions->group_attribute_id,
                    'self_ecommerce_identify' => $responseAttrVariationOptions->self_ecommerce_identify,
                    'options' => $responseAttrVariationOptions->options,
                ];
            }
        }

        $this->sendAndPrepareOptionsVariationsComplete(
            $productParent->sku,
            $listOfAttrVariationsProduct,
            $this->consumer
        );

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after sendAndPrepareOptionsVariationsComplete');

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') before send variation to Queue');

        foreach ($childItems as $variationItem) {

            $configsVariations['last_run']->addSeconds(rand(37, 70));
            \Log::debug(__CLASS__.' ('.__FUNCTION__.') before createVariationItem');

            $this->createVariationItem(
                $this->productnstance,
                [
                    'variations_attributes' => $listOfAttrVariationsProduct,
                ],
                $variationItem,
                $configsVariations['last_run']
            );

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') after createVariationItem');

        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after send variation to Queue');
    }


    private function sendAndPrepareOptionsVariationsComplete($productSku, $arrAttrVariations, $consumer)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        foreach ($arrAttrVariations as $arrAttrItem) {

            $consumer->attachOptionAttibuteAttrIntoConfigurableProduct($productSku, [
                'option' => [
                    'attribute_id' => (int) $arrAttrItem['self_ecommerce_identify'],
                    'label' => $arrAttrItem['name'],
                    'position' => 0,
                    "is_use_default" => true,
                    'values' => collect($arrAttrItem['options'] ?? [])->map( function ($item) {
                                    return [
                                        'value_index' => (int) $item['value']
                                    ];
                            })->values()->toArray(),
                ]
            ]);

            usleep(rand(20, 60));
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');
    }

    private function createVariationItem($productParent, $auxArr , $childItem, $lastCarbonInstance)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') send to SendProductChidrenAndAttachParentJob::dispatch');

        SendProductChidrenAndAttachParentJob::dispatch(
            (new ProductDto())->fill($childItem),
            $productParent,
            $this->consumer,
            [
                'last_carbon_time_dispatch' => $lastCarbonInstance,
                'variations_attributes' => $auxArr['variations_attributes'],
            ])->delay($lastCarbonInstance);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');
    }


    private function getCategoriesHierarquies($uuId)
    {
        return  ProductCategory::where('uuid', $uuId)
                                ->first()
                                ->getFullHierarchy();

    }

    private function createAttributeSet(array $params): array
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        return  (new FindOrCreateProductGroupAttributeAction)
                ->execute(collect([
                    'slug' => $params['slug'],
                    'breadcrumb' => $params['breadcrumb'],
                    'group_attribute_name' => $params['group_attribute_name'],
                ]), $this->consumer);
    }

    private function createAttributeSetAttributesVariations(array $params)
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = (new FindOrCreateProductGroupAttributeOptionVariationItemsAction)
                ->execute(collect([
                    'group_attribute_id' => $params['group_attribute_id'],
                    'item' => $params['item'],
                    'option' => $params['option'],
            ]),
            [
                    'group_attribute_subgroup_id' => $params['group_attribute_subgroup_id'],
                    'group_attribute_id' => $params['group_attribute_id'],
                    'sufix' => '_option',
            ],
                $this->consumer);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function createAttributeSetAttributes(array $params): array
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = [];
        // foreach ($params['items'] as $itemAttrItems) {
        //     foreach ($itemAttrItems['rows'] as $itemAttr) {
        foreach ($params['items'] as $itemAttr) {
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

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function getFormatedCustomAttributesList($params): array
    {
        $returnData = [];

        // foreach ($params['items'] as $itemAttrItems) {
        //     foreach ($itemAttrItems['rows'] as $itemAttr) {
        foreach ($params['items'] as $itemAttr) {
            $returnData[] = [
                'attribute_code' => Str::slug($itemAttr['label'], '_').$params['sufix'],
                'value' => $itemAttr['value'],
            ];
        }


        $returnData[] = [
            "attribute_code" => "description",
            "value" => $this->getSafeHtmlCharsToJson($this->productnstance->description['complement']['html'] ?? "description"),
        ];

        $returnData[] = [
            "attribute_code" => "short_description",
            "value" => $this->getSafeHtmlCharsToJson($this->productnstance->description['small']['html'] ?? "short_description"),
        ];

        $returnData[] = [
            "attribute_code" => "url_key",
            "value" =>  Str::slug($this->productnstance->title.'-base', '-'),
        ];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finish');

        return $returnData;
    }

    private function getSafeHtmlCharsToJson($html)
    {
        $html = preg_replace('/\s+/', ' ', $html);

        $parts = preg_split('/<br\s*\/?>/i', $html);
        $parts = array_filter(array_map('trim', $parts));

        $html = '';
        foreach ($parts as $part) {
            $html .= "<p>{$part}</p>";
        }

        $html = strip_tags($html, '<p><ul><li><a><strong><b>');

        $html = preg_replace_callback(
            '/<(a|p|ul|li|strong|b)([^>]*)>/i',
            function ($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];

                $allowed = '';

                if (preg_match('/href\s*=\s*"(.*?)"/i', $attrs, $m)) {
                    $allowed .= ' href="' . $m[1] . '"';
                }

                if (preg_match('/target\s*=\s*"(.*?)"/i', $attrs, $m)) {
                    $allowed .= ' target="' . $m[1] . '"';
                }

                return "<{$tag}{$allowed}>";
            },
            $html
        );

        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
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
                "price" => $this->hasVariations == false ? ($productData->price['current'] ?? 0) : 0,
                "status" => 1,
                "visibility" => 4,
                "type_id" => $this->typeProduct,
                "weight" => 1,
                "extension_attributes" => $extensionAttributes,
                'custom_attributes' => $this->getFormatedCustomAttributesList([
                    'items' => $this->productnstance?->specifications ?? [],
                    'sufix' => '_text'
                ])
            ]
        ];

        if (!$this->hasVariations) {
            $payload['saveOptions'] = true;
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') prepare to send $this->consumer->createProduct', $payload);

        return $this->consumer->createProduct($payload);
    }

    private function createImagesIntoProduct($productSku, array $images,  $delayToJob): array
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');
        //@TODO remover isso depois
        // return [];

        foreach ($images as $img) {

            $delayToJob->addSeconds(rand(15,40));

            UploadImageJpgToSelfCommerceToProductJob::dispatch(
                $productSku,
                $img['full_size'],
                    $this->consumer
            )->delay($delayToJob);

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') enviando item para UploadImageJpgToSelfCommerceToProductJob');

        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished');

        return [
            'last_run' => $delayToJob,
        ];
    }



}
