<?php

namespace App\Actions;

use App\Consumers\MercadoLivreScrapperConsumer;

use App\Models\{ProductCentral,
                ProductMl
                };

class AttachMercadoLivreProductToProductCentralAction
{
    public function execute(ProductCentral $instance)
    {
        $consumer = new MercadoLivreScrapperConsumer([
            'base_path' => config('custom-services.apis.mercado_livre_scrapper.base_path')
        ]);

        \Log::info("(AttachMercadoLivreProductToProductCentralAction) Buscando ".$instance->sku." em : ".$instance->url_product_ml);

        $consumer->invokeProductWebHook([
            'targetUrl' => $instance->url_product_ml,
            'productName' => $instance->ploutos_descricao ?? "",
            'callbackUrl' => config('custom-services.apis.mercado_livre_scrapper.callback_url'),
            'externalId' => $instance->uuid,
        ]);

        \Log::info("(AttachMercadoLivreProductToProductCentralAction)".$instance->sku." agendado com sucesso");
    }
}
