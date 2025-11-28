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

use App\Jobs\{UploadImageJpgToSelfCommerceToProductJob,
             SendProductChidrenAndAttachParentJob};

use Illuminate\Support\Str;
use Carbon\Carbon;
class CreateProductChildSelfEcommerceUseCase
{
    private $consumer;
    private $typeProduct;
    private $productnstance;
    private $parentProduct;
    private $configs;

    public function __construct(SelfEcommerceConsumer $consumer,
                                ProductDto $productnstance,
                                ProductRewrited $parentProduct,
                                $configs)
    {
        $this->consumer = $consumer;
        $this->configs = $configs;
        $this->productnstance = $productnstance;
        $this->parentProduct = $parentProduct;
    }

    public function handle()
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init DEBUG',['$this->configs' => $this->configs]);

        $delayToJob = $this->configs['last_carbon_time_dispatch'];

        $categoryAttrs = $this->parentProduct?->productCentral()->first()->category()->first() ?? null;
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

        $this->typeProduct = 'simple';

        $this->productnstance->attribute_set_id = $attributeSetArr['self_ecommerce_identify'];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createProduct');

        $this->createProduct(
            $this->productnstance,
            $this->parentProduct,
        );

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createProduct');


        \Log::info(__CLASS__.' ('.__FUNCTION__.') before createImagesIntoProduct');

        $this->createImagesIntoProduct(
                                $this->productnstance->sku,
                                $this->productnstance->images ?? [],
                                $delayToJob);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after createImagesIntoProduct');


        $attachChildResponde = $this->consumer->attachProductChildIntoConfigurableProduct(
            $this->parentProduct->sku,
            [
                'childSku' => $this->productnstance->sku
            ]
        );

        \Log::info(__CLASS__.' ('.__FUNCTION__.') result of attachProductChildIntoConfigurableProduct', [
            $attachChildResponde
        ]);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finish');

        return $this->productnstance;
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
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $returnData = [];
        // foreach ($params['items'] as $itemAttrItems) {
        //     foreach ($itemAttrItems['rows'] as $itemAttr) {
        foreach ($params['items'] as $itemAttr) {
            $returnData[] = [
                'attribute_code' => Str::slug($itemAttr['label'], '_').$params['sufix'],
                'value' => $itemAttr['value'],
            ];
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after configurable attributes');

        $returnData[] = [
            "attribute_code" => "description",
            "value" => $this->getSafeHtmlCharsToJson($this->productnstance->description['complement']['html'] ?? "description"),
        ];


        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after description');

        $returnData[] = [
            "attribute_code" => "short_description",
            "value" => $this->getSafeHtmlCharsToJson($this->productnstance->description['small']['html'] ?? "short_description"),
        ];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after short_description DEBUG:',$returnData);

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

    private function createProduct($productData, $parentProduct): ?array
    {
        $listCategories = $this->getCategoriesHierarquies($parentProduct->productCentral()->first()->category_id);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

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

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') declare extensionAttributes',  [
            '$listCategories->count() > 0' => $listCategories->count() > 0
        ]);

        if ($listCategories->count() > 0) {
            $extensionAttributes['category_links'] = $listCategories->map(function ($category, $index) {
                return [
                    'position' => $index,
                    'category_id' => $category->self_ecommerce_id ?? 'none',
                ];
            })->values()->toArray();
        }

        $customAttrSelfPrd = $this->getFormatedCustomAttributesList([
            'items' => $parentProduct?->specifications ?? [],
            'sufix' => '_text'
        ]);

        $customAttrVariationsComplete = [];
        $slugPartsGlobal = [];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') prepare declare $payload');

        $customAttrVariationsComplete = $this->parseCustomAttributesFromThisChild($productData, $this->configs['variations_attributes']);
        if (!empty($customAttrVariationsComplete['slug_combined'])) {
            $slugPartsGlobal[] = $customAttrVariationsComplete['slug_combined'];
        }

        $customAttrSelfPrd[] = [
            "attribute_code" => "url_key",
            "value" =>  Str::slug($productData->title.'-modelo', '-').'-'.str_replace(
                            ['_option'],
                            [],
                            implode('-', $slugPartsGlobal)
                        ),
        ];

        $payload = [
            "product" => [
                "sku" => $this->generateSkuToThisProduct($parentProduct->sku , $slugPartsGlobal),
                "name" => $productData->title,
                "attribute_set_id" => $productData->attribute_set_id,
                "price" => $productData->price['current'],
                "status" => 1,
                "visibility" => 4,
                "type_id" => $this->typeProduct,
                "weight" => 1,
                "extension_attributes" => $extensionAttributes,
                'custom_attributes' => array_merge(
                    $customAttrSelfPrd,
                    $customAttrVariationsComplete['attributes']
                )
            ]
        ];

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') after declare $payload DEBUG', [
            '$customAttrSelfPrd' => $customAttrSelfPrd,
            'customAttrVariationsComplete[attributes]' => $customAttrVariationsComplete['attributes'],
        ]);


        \Log::debug(__CLASS__.' ('.__FUNCTION__.') prepare to send $this->consumer->createProduct', $payload);

        return $this->consumer->createProduct($payload);
    }


    private function generateSkuToThisProduct($baseSku, $slugPartsGlobal)
    {
        $rawSlug = implode('_', $slugPartsGlobal);

        $rawSlug = str_replace(['_option', ' ', '.'], ['', '', ''], $rawSlug);
        $rawSlug = Str::ascii($rawSlug);
        $rawSlug = preg_replace('/[^A-Za-z0-9_]/', '', $rawSlug);

        $base = strtoupper(Str::slug($baseSku, '_'));
        $slug = strtoupper($rawSlug);

        $sku = $base . '_' . $slug;

        if (strlen($sku) > 64) {

            $parts = explode('_', $slug);
            $abbreviated = [];

            foreach ($parts as $word) {

                if (preg_match('/^[0-9]+[A-Z]*$/i', $word)) {
                    $abbreviated[] = $word;
                    continue;
                }

                $len = strlen($word);

                if ($len <= 3) {
                    $abbreviated[] = $word;
                    continue;
                }

                if ($len >= 4 && $len <= 6) {
                    $abbreviated[] = substr($word, 0, 3);
                    continue;
                }

                $wordOnlyConsonants = preg_replace('/[AEIOU]/i', '', $word);
                $abbreviated[] = substr($wordOnlyConsonants, 0, 5);
            }

            $slug = implode('_', $abbreviated);
            $sku = $base . '_' . $slug;

            if (strlen($sku) > 64) {
                $maxSlugLen = 64 - strlen($base) - 6;
                $slug = substr($slug, 0, max(0, $maxSlugLen));

                $randomChars = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);

                $sku = $base . '_' . $slug . '_' . $randomChars;
            }
        }

        $skuLimited = substr($sku, 0, 63);

        $this->productnstance->sku = $skuLimited;

        return $skuLimited;
    }




    private function parseCustomAttributesFromThisChild($currentProduct, array $attributesSelf): array
    {
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') init');

        $attrReturn = [];
        $slugParts = [];

        foreach ($attributesSelf as $itemAllAttr) {
            foreach ($currentProduct->attributes as $currentItemAllAttrPrd) {
                foreach ($itemAllAttr['options'] as $itemAllOption) {
                    if ($itemAllAttr['name'] ==  $currentItemAllAttrPrd[0]['label'] &&
                        $itemAllOption['label'] ==  $currentItemAllAttrPrd[1]['value']
                    ) {
                        $attrReturn[] = [
                            'attribute_code' => $itemAllAttr['slug'],
                            'value' => $itemAllOption['value'],
                        ];

                         $slugParts[] = strtolower(
                            $itemAllAttr['slug'] . '_' . $itemAllOption['label']
                        );
                    }
                }
            }
        }

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished');

        return [
            'attributes' => $attrReturn,
            'slug_combined' => implode('_', $slugParts),
        ];

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
