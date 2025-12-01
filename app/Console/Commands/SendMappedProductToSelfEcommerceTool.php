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
            ->where('synced_self_ecommerce', false)
            // ->where('sku', 'PM04006090')
            ->get();

        \Log::info("(SendMappedProductToSelfEcommerceTool) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $indexPending => $pending) {
            if ($indexPending > 0) {
                $delayToJob->addMinutes(rand(10, 30));
            }

            SendMappedProductToSelfEcommerceJob::dispatch( $pending)
                                 ->delay($delayToJob);

           \Log::info("(SendMappedProductToSelfEcommerceTool) Job para item ".($pending->sku ?? 'sku')." para envio ao ecommerce com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMappedProductToSelfEcommerceTool) Processo finalizado");

    }
}
