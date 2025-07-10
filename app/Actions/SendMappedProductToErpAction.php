<?php

namespace App\Actions;

use App\Consumers\{BlingErpConsumer,
                  BlingOauthConsumer};

use App\Models\ProductCentral;

use App\Resources\ProductToErpTransformResource;

class SendMappedProductToErpAction
{
    public function execute(ProductCentral $productCentral)
    {
        $dataTransformed = (new ProductToErpTransformResource($productCentral))->toArray([]);

        $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
            'auto_login' => true,
            'base_path' => config('custom-services.apis.bling_erp.base_path'),
        ]);

        return $consumer->createProduct($dataTransformed);
    }
}
