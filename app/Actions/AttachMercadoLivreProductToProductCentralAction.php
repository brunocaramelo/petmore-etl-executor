<?php

namespace App\Actions;

use App\Consumers\MercadoLivreScrapperConsumer;

use App\Models\ProductCentral;

class AttachMercadoLivreProductToProductCentralAction
{
    public function execute(ProductCentral $instance)
    {
        $consumer = new MercadoLivreScrapperConsumer([
            'base_path' => config('custom-services.apis.mercado_livre_scrapper.base_path')
        ]);

        \Log::info("(AttachMercadoLivreProductToProductCentralAction) Buscando ".$instance->sku." em : ".$instance->url_product_ml);

        $responseApi = $consumer->getProductByUrl($instance->url_product_ml ?? 'none');

        $instance->productMl()->create($responseApi);

        $instance->synced_ml = true;

        $instance->save();

        \Log::info("(AttachMercadoLivreProductToProductCentralAction)".$instance->sku." importado com sucesso");
    }
}
