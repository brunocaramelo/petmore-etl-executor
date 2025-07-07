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

        $responseApi = $consumer->getProductByUrl($instance->url_product_ml ?? 'none');

        $responseApi['ml_identify'] = $responseApi['id'] ?? 'not_found';

        if(isset($responseApi['id'])) {
            unset($responseApi['id']);
        }
        if(isset($responseApi['_id'])) unset($responseApi['_id']);

        $instance->product_ml_id = ProductMl::create($responseApi)->uuid;

        $instance->synced_ml = true;
        $instance->ml_identify = $responseApi['ml_identify'];

        $instance->save();


        \Log::info("(AttachMercadoLivreProductToProductCentralAction)".$instance->sku." importado com sucesso");
    }
}
