<?php

namespace App\UseCases;

use App\Consumers\SelfEcommerceConsumer;

use App\Actions\SelfEcommerce\{FindOrCreateProductGroupAttributeAction,
                               FindOrCreateProductGroupAttributeItemsAction};

use App\Models\{ProductRewrited,
                ProductCategory
                };

use App\UseCases\CreateProductChildSelfEcommerceUseCase;

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
        $productVatiations = $this->productnstance->variations ?? [];
        $categoryAttrs = $this->productnstance->category;
        $categoryAttrsProductAttributesItems = $this->productnstance->specifications;

        $attributeSetId = $this->createAttributeSet([
            'slug' => $categoryAttrs->slug,
            'breadcrumb' => $categoryAttrs->hierarquie,
        ]);

        $attributeSetAttributesList = $this->createAttributeSetAttributes([
            'attribute_set_id' => $attributeSetId,
            'items' => $categoryAttrsProductAttributesItems,
        ]);

        $this->productnstance->attribute_set_id = $attributeSetId;
        $this->productnstance->specifications = $attributeSetAttributesList;

        $configurableProduct = $this->createProduct($this->productnstance);

        foreach ($productVatiations as $variationItem) {
            $this->createVariationItem(json_decode(json_encode($variationItem)));
        }


        $this->createImagesIntoProduct($this->productnstance->sku, $this->productnstance->images ?? []);

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

    private function createAttributeSet(array $params): int
    {
        return (new FindOrCreateProductGroupAttributeAction)
                ->execute(collect([
                    'slug' => $params['slug'],
                    'breadcrumb' => $params['breadcrumb'],
                ]), $this->consumer)['self_ecommerce_identify'];
    }

    private function createAttributeSetAttributes(array $params): array
    {
        $returnData = [];
        foreach ($params as $itemAttr) {
            $returnData[] = (new FindOrCreateProductGroupAttributeItemsAction)
                    ->execute(collect([
                        'group_attribute_id' => $params['group_attribute_id'],
                        'item' => $itemAttr->rows,
                ]), $this->consumer)['self_ecommerce_identify'];
        }
        return $returnData;
    }

    private function createProduct(ProductRewrited $productData): ?int
    {
        $listCategories = $this->getCategoriesHierarquies($productData->productCentral);

        $extensionAttributes = [
            'extension_attributes' => [
            'stock_item' => [
                'qty' => 0,
                'is_in_stock' => true,
                'manage_stock' => true,
                'use_config_manage_stock' => false,
                'min_qty' => 0,
                'use_config_min_qty' => false,
                'min_sale_qty' => 1,
                'use_config_min_sale_qty' => false,
                'max_sale_qty' => 100,
                'use_config_max_sale_qty' => false,
                'is_qty_decimal' => false,
                'backorders' => 0,
                'use_config_backorders' => false,
                'notify_stock_qty' => 0,
                'use_config_notify_stock_qty' => false
                ],
            ]
        ];

        if ($listCategories->count() > 0) {
            $extensionAttributes['extension_attributes']['category_links'] = $listCategories->map(function ($index, $category) {
                return [
                    'position' => $index,
                    'category_id' => $category->id,
                ];
            })->toArray();
        }

        $payload = [
            "product" => [
                "sku" => $productData->sku,
                "name" => $productData->name,
                "attribute_set_id" => $productData->attribute_set_id,
                "price" => $productData->price->current,
                "status" => 1,
                "visibility" => 2,
                "type_id" => $this->typeProduct,
                "weight" => 1,
                "extension_attributes" => $extensionAttributes,
                "custom_attributes" => $productData['custom_attributes'] ?? []
            ]
        ];

        return $this->consumer->createProduct($payload);
    }

    private function createImagesIntoProduct($productSku, array $images): void
    {
        foreach ($images as $img) {

            $path = "/var/www/html/media" . $img['file'];
            if (!file_exists($path)) {
                echo "⚠️ Imagem não encontrada: {$path}\n";
                continue;
            }

            $payload = [
                "entry" => [
                    "media_type" => "image",
                    "label" => $img['label'] ?? '',
                    "position" => $img['position'] ?? 1,
                    "disabled" => false,
                    "types" => $img['types'] ?? ['image', 'small_image', 'thumbnail'],
                    "content" => [
                        "base64_encoded_data" => base64_encode(file_get_contents($path)),
                        "type" => "image/jpeg",
                        "name" => basename($path)
                    ]
                ]
            ];

            $this->consumer->createMediaImagesIntoProductSku($productSku, $payload);
        }
    }

}
