<?php

namespace App\UseCases;

use App\Consumers\SelfEcommerceConsumer;

use App\Actions\SelfEcommerce\{FindOrCreateProductGroupAttributeAction,
                               FindOrCreateProductGroupAttributeItemsAction};

use App\Models\{ProductRewrited,
                ProductCategory
                };

use App\UseCases\CreateProductChildSelfEcommerceUseCase;

use Illuminate\Support\Facades\Storage;

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
        $this->productnstance->specifications = $attributeSetAttributesList;

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createProduct');

        $configurableProduct = $this->createProduct($this->productnstance);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createProduct');


        // foreach ($productVatiations as $variationItem) {
        //     $this->createVariationItem(json_decode(json_encode($variationItem)));
        // }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createImagesIntoProduct');

        $this->createImagesIntoProduct($this->productnstance->sku, $this->productnstance->images ?? []);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createImagesIntoProduct');


        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $this->productnstance;
    }

    private function createVariationItem($params)
    {
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
                    'group_attribute_name' => 'Attributes',
                ]), $this->consumer);
    }

    private function createAttributeSetAttributes(array $params): array
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = [];
        foreach ($params['items'] as $itemAttrItems) {
            foreach ($itemAttrItems['rows'] as $itemAttr) {

            $returnData[] = (new FindOrCreateProductGroupAttributeItemsAction)
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
                "custom_attributes" => $productData['custom_attributes'] ?? []
            ]
        ];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') prepare to send $this->consumer->createProduct', $payload);

        return $this->consumer->createProduct($payload);
    }

    private function createImagesIntoProduct($productSku, array $images): void
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $clearHttpPathStorage = 'https://petmore-public.br-se1.magaluobjects.com/petmore-public/';

        foreach ($images as $img) {

            $clearPath = str_replace([$clearHttpPathStorage],[''], $img['full_size']);

            $fileContentTarget = Storage::disk('choiced_cloud_storage')->get($clearPath);

            $payload = [
                "entry" => [
                    "media_type" => "image",
                    "label" => $img['label'] ?? '',
                    "position" => $img['position'] ?? 1,
                    "disabled" => false,
                    "types" => $img['types'] ?? ['image', 'small_image', 'thumbnail'],
                    "content" => [
                        "base64_encoded_data" => base64_encode($fileContentTarget),
                        "type" => "image/jpeg",
                        "name" => basename($clearPath)
                    ]
                ]
            ];

            \Log::info(__CLASS__.' ('.__FUNCTION__.') before send createMediaImagesIntoProductSku', [
               $productSku,
                $payload
            ]);

            $this->consumer->createMediaImagesIntoProductSku($productSku, $payload);

            \Log::info(__CLASS__.' ('.__FUNCTION__.') after send createMediaImagesIntoProductSku');

        }

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finished');
    }

}
