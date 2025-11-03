<?php

namespace App\Console\Commands;

use App\Models\ProductCentral;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Jobs\SendMappedProductToSelfEcommerceJob;

class SendMappedProductToSelfEcommerceTool extends Command
{

    protected $signature = 'export:mapped-product-to-self-ecommerce-tool';
    protected $description = 'Envio de dados mapeados de produto ao Ecommerce proprio.';

    public function handle()
    {
        $delayToJob = Carbon::now();

        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productRewrited')
            ->with('productRewrited')
            ->where('ai_adapted_the_content', true)
            ->where('sku', '=' ,'PM12405432')
            ->get();

        \Log::info("(SendMappedProductToSelfEcommerceTool) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $pending) {

            // $delayToJob->addSeconds(rand(61, 123));
            $delayToJob->addSeconds(rand(1, 2));

            SendMappedProductToSelfEcommerceJob::dispatch( $pending)
                                 ->delay($delayToJob);

           \Log::info("(SendMappedProductToSelfEcommerceTool) Job para item ".($pending->sku ?? 'sku')." para envio ao bling com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMappedProductToSelfEcommerceTool) Processo finalizado");

    }
}
