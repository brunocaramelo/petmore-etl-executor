<?php

use Illuminate\Support\Facades\Route;

use App\Consumers\BlingOauthConsumer;
use App\Consumers\BlingErpConsumer;
use Illuminate\Support\Str;
use App\Consumers\SelfEcommerceAuthConsumer;
use App\Consumers\SelfEcommerceConsumer;

use App\Models\ProductCategory;
use App\Models\ProductSelfCommerceData;

Route::get('/manter-categorias-bling', function () {

    $content = \Storage::disk('local')->get('ploutos-plans/categorias-bling.json');
    $parsed = json_decode($content);

/**
 * Função recursiva para processar uma categoria e suas filhas.
 */
function processCategoryRecursive($category, $parent = null, $hierarchy = [])
{
    $slug = Str::slug($category->descricao);

    $currentHierarchy = array_merge($hierarchy, [$slug]);

    $fullSlug = implode('-', $currentHierarchy);

    $categoryModel = ProductCategory::firstOrCreate(
        ['slug' => $fullSlug],
        [
            'name' => $category->descricao,
            'hierarquie' => $currentHierarchy,
            'slug' => $fullSlug,
            'bling_identify' => $category->id,
            'bling_parent_identify' => $parent?->id,
            'parent_id' => $parent?->uuid,
        ]
    );

    if (!empty($category->filha)) {
        foreach ($category->filha as $child) {
                processCategoryRecursive($child, $categoryModel, $currentHierarchy);
            }
        }
    }

    foreach ($parsed->data as $rootCategory) {
        processCategoryRecursive($rootCategory);
    }

    die('processou ok ✅');

});


Route::get('/refreshtoken', function () {
    $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
             'auto_login' => true,
             'base_path' => config('custom-services.apis.bling_erp.base_path'),
         ]);
});

Route::get('/magento-get-new-products', function () {
     $consumerInstance = new SelfEcommerceConsumer(
                new SelfEcommerceAuthConsumer(
                    config('custom-services.apis.self_ecommerce.admin_username'),
                    config('custom-services.apis.self_ecommerce.admin_password')
                ), [
            'base_path' => config('custom-services.apis.self_ecommerce.domain_url'),
            'auto_login' => true,
        ]);

    $filters = [
        'currentPage' => 0,
        'pageSize' => 20,
    ];

    $resultResponse = $consumerInstance->getProductsFilterPaginate($filters);

    while(!empty($resultResponse['items'])) {

        $resultResponse = $consumerInstance->getProductsFilterPaginate($filters);
        $filters['currentPage'] =  ($filters['currentPage'] +1);

        foreach ($resultResponse['items'] as $itemRes) {
            if(ProductSelfCommerceData::where('entity_id', $itemRes['id'])->exists()) {
                continue;
            }


            \Log::info('add '.json_encode($itemRes));

            ProductSelfCommerceData::create([
                "entity_id" => $itemRes['id'],
                "sku" => $itemRes['sku'],
                "TYPE" => "simple",
                "NAME" => $itemRes['name'],
                "URL" => config('custom-services.apis.self_ecommerce.domain_url').'/'.($itemRes['url_key'] ?? 'not_found'),
                "parent_sku" => null,
                "has_variation" => 0,
                "ean" => null,
                "has_searched" => true,
                "height" => null,
                "length" => null,
                "official_data_sended" => false,
                "weight" => null,
                "width" => null
            ]);
        }
    }



    die(json_encode($resultResponse));


});
