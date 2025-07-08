<?php

namespace App\Console\Commands;

use App\Models\ProductCentral;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Jobs\SendMappedProductToErpJob;

use App\Actions\SendMappedProductToErpAction;

class SendMappedProductToErp extends Command
{

    protected $signature = 'export:mapped-product-to-erp';
    protected $description = 'Envio de dados mapeados de produto ao ERP.';

    public function handle()
    {
        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productRewrited')
            ->with('productRewrited')
            ->where('ai_adapted_the_content', true)
            ->get();

        \Log::info("(SendMappedProductToErp) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $pending) {

            $delayToJob = Carbon::now()->addSeconds(rand(25,54));

            SendMappedProductToErpJob::dispatch(new SendMappedProductToErpAction(), $pending)
                                 ->delay($delayToJob);

           \Log::info("(SendMappedProductToErp) Job para item ".($pending->sku ?? 'sku')." de busca no mercado livre despachado com atraso para: " . $delayToJob);

        }

    }
}
