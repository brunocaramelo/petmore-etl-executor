<?php

namespace App\Actions;

use App\Consumers\{BlingErpConsumer,
                  BlingOauthConsumer};

use App\Models\ProductCentral;

use App\Resources\ProductToErpTransformResource;
use Illuminate\Http\Client\RequestException;

class SendMappedProductToErpAction
{
    public function execute(ProductCentral $productCentral)
    {
        try {

        $dataTransformed = (new ProductToErpTransformResource($productCentral))->toArray([]);

        $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
            'auto_login' => true,
            'base_path' => config('custom-services.apis.bling_erp.base_path'),
        ]);

        // \Log::info('(SendMappedProductToErpAction) payload para o bling abaixo sem: ');
        // \Log::info(json_encode($dataTransformed));

        return $consumer->createProduct($dataTransformed);

        } catch (RequestException $e) {
            \Log::error('Erro em SendMappedProductToErpAction:', [
                    'status' => $e->response->status(),
                    'response_body' => $e->response->json(),
            ]);

            throw new \Exception('SendMappedProductToErpAction exception : '.$e->getMessage());
        }

    }
}
