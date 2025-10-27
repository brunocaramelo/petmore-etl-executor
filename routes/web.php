<?php

use Illuminate\Support\Facades\Route;

use App\Consumers\BlingOauthConsumer;
use App\Consumers\BlingErpConsumer;
use Illuminate\Support\Str;

use App\Models\ProductCategory;

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


// Route::get('/just-test', function () {
//    \App\Models\ProductCentral::raw()->updateMany(
//         [], // O filtro está vazio, o que seleciona todos os documentos
//         [
//             '$set' => [
//                 'category_id' => '3c82a076-f6d2-40c0-a8f0-c962090330c9'
//             ]
//         ]
//     );
// });

Route::get('/refreshtoken', function () {
    $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
             'auto_login' => true,
             'base_path' => config('custom-services.apis.bling_erp.base_path'),
         ]);
});
