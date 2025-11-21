<?php

namespace App\Actions;

use App\Consumers\MercadoLivreScrapperConsumer;

use App\Models\{ProductCentral,
                ProductMl
                };

class AttachCallbackMercadoLivreProductToProductCentralAction
{
    public function execute($param)
    {
        $instance = ProductCentral::where('uuid', $param['external_id'])->first();

        $responseApi = $param['data'];

        $responseApi['ml_identify'] = $responseApi['id'] ?? 'not_found';

        if(isset($responseApi['id'])) {
            unset($responseApi['id']);
        }

        if(isset($responseApi['_id'])) unset($responseApi['_id']);

        if(!empty($responseApi['variations'])
            && is_array($responseApi['variations'])
            && count($responseApi['variations']) == 1
            && !empty($responseApi['variations'][0])
            && empty($responseApi['variations'][0]['attributes'])
        ) {
            \Log::info("(AttachAsyncMercadoLivreProductToProductCentralAction) Variação com um unico item e sem atributos, variação incorreta, se trata de um produto simples", [
                'variations_debug' => $responseApi['variations'],
            ]);

            $responseApi['variations'] = [];
        }

        $instance->product_ml_id = ProductMl::create($responseApi)->uuid;

        $instance->synced_ml = true;
        $instance->ml_identify = $responseApi['ml_identify'];

        $instance->save();


        \Log::info("(AttachAsyncMercadoLivreProductToProductCentralAction)".$instance->sku." importado com sucesso");
    }
}
