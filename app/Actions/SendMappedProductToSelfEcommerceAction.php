<?php

namespace App\Actions;

use App\Models\ProductCentral;

use App\UseCases\CreateProductBaseSelfEcommerceUseCase;
use Exception;

use App\Consumers\{SelfEcommerceAuthConsumer,
                  SelfEcommerceConsumer};

class SendMappedProductToSelfEcommerceAction
{
    public function execute(ProductCentral $productCentral)
    {
        try {
        $consumerInstance = new SelfEcommerceConsumer(
                new SelfEcommerceAuthConsumer(
                    config('custom-services.apis.self_ecommerce.admin_username'),
                    config('custom-services.apis.self_ecommerce.admin_password')
                ), [
            'base_path' => config('custom-services.apis.self_ecommerce.base_url'),
            'auto_login' => true,
        ]);

        (new CreateProductBaseSelfEcommerceUseCase($consumerInstance ,
                $productCentral->productRewrited))
            ->handle();

        // \Log::info('(SendMappedProductToErpAction) payload para o bling abaixo sem: ');
        // \Log::info(json_encode($dataTransformed));

        } catch (Exception $e) {
            report($e);

            throw new \Exception('SendMappedProductToErpAction exception : '.$e->getMessage());
        }

    }
}
