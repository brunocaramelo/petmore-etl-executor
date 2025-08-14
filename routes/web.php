<?php

use Illuminate\Support\Facades\Route;

use App\Consumers\BlingOauthConsumer;
use App\Consumers\BlingErpConsumer;
use Illuminate\Support\Str;

use App\Models\ProductCategory;

Route::get('/manter-categorias-bling', function () {

    $content = \Storage::disk('local')->get('ploutos-plans/categorias-bling.json');
    $parsed = json_decode($content);

    foreach($parsed->data as $open) {
        $open->slug = Str::slug($open->descricao);

        $categoryParent = ProductCategory::firstOrCreate(
            ['slug' => $open->slug]
            ,[
                'name' => $open->descricao,
                'hierarquie' => [$open->slug],
                'slug' => $open->slug,
                'bling_identify' => $open->id,
            ]
        );

        foreach ($open->filha as $child) {
            $slugItem = Str::slug($child->descricao);
            $child->slug = Str::slug($open->slug.' '.$slugItem);

            ProductCategory::firstOrCreate(
                ['slug' => $child->slug]
                ,[
                    'name' => $child->descricao,
                    'hierarquie' => [$categoryParent->slug, $slugItem],
                    'slug' => $child->slug,
                    'bling_identify' => $child->id,
                    'bling_parent_identify' => $child->id,
                    'parent_id' => $categoryParent->uuid,
                ]
            );

        }


    }

    die('processou ok');
});


Route::get('/just-test', function () {
//    \App\Models\ProductCentral::raw()->updateMany(
//         [], // O filtro está vazio, o que seleciona todos os documentos
//         [
//             '$set' => [
//                 'category_id' => '8da30a83-0727-4a19-9dd8-1816a4bfd73b'
//             ]
//         ]
//     );
});

Route::get('/refreshtoken', function () {
    $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
             'auto_login' => true,
             'base_path' => config('custom-services.apis.bling_erp.base_path'),
         ]);
});
