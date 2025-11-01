<?php

namespace App\UseCases;

use App\Consumers\SelfEcommerceConsumer;

use App\Resources\ProductToSelfEccommerceTransformResource;
use App\Actions\SelfEcommerce\FindOrCreateProductGroupAttributeAction;
use App\Models\ProductRewrited;

class CreateProductSelfEcommerceUseCase
{
    private $consumer;
    private $typeProduct;
    private $productnstance;

    public function __construct(SelfEcommerceConsumer $consumer,
                                string $typeProduct,
                                ProductRewrited $productnstance)
    {
        $this->consumer = $consumer;
        $this->productnstance = $productnstance;
    }

    public function handle()
    {
        $categoryAttrs = $this->productnstance->category;

        $attributeSetId = $this->createAttributeSet([
            'slug' => $categoryAttrs->slug,
            'breadcrumb' => $categoryAttrs->hierarquie,
        ]);

        $this->productnstance->attribute_set_id = $attributeSetId;

        $configurableId = $this->createProduct($this->productnstance);

        $this->createConfigurableChildren($configurableId, $productData);

        $this->uploadImages($productData['sku'], $productData['media_gallery_entries'] ?? []);

        return $configurableId;
    }


    private function createAttributeSet(array $params): int
    {
        return (new FindOrCreateProductGroupAttributeAction)
                ->execute(collect([
                    'slug' => $params['slug'],
                    'breadcrumb' => $params['breadcrumb'],
                ]), $this->consumer)['self_ecommerce_identify'];
    }

    private function createProduct(ProductRewrited $productData): ?int
    {
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

        $response = $this->callMagento('products', 'POST', $payload);
        return $response['id'] ?? null;
    }

    private function createConfigurableChildren(?int $parentId, array $productData): void
    {
        if (empty($productData['extension_attributes']['configurable_product_links'])) return;

        foreach ($productData['extension_attributes']['configurable_product_links'] as $childId) {
            $payload = [
                "childSku" => "CHILD-{$childId}" // ou SKU real do simples
            ];
            $this->callMagento("configurable-products/{$productData['sku']}/child", 'POST', $payload);
        }
    }

    private function uploadImages(string $sku, array $images): void
    {
        foreach ($images as $img) {
            $path = "/var/www/html/media" . $img['file'];
            if (!file_exists($path)) {
                echo "⚠️ Imagem não encontrada: {$path}\n";
                continue;
            }

            $base64 = base64_encode(file_get_contents($path));

            $payload = [
                "entry" => [
                    "media_type" => "image",
                    "label" => $img['label'] ?? '',
                    "position" => $img['position'] ?? 1,
                    "disabled" => false,
                    "types" => $img['types'] ?? ['image', 'small_image', 'thumbnail'],
                    "content" => [
                        "base64_encoded_data" => $base64,
                        "type" => "image/jpeg",
                        "name" => basename($path)
                    ]
                ]
            ];

            $this->callMagento("products/{$sku}/media", 'POST', $payload);
        }
    }

}
