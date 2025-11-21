<?php

namespace App\Console\Commands;

use App\Models\ProductCentral;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Jobs\SendMappedProductToErpJob;

class SendMappedProductToErp extends Command
{

    protected $signature = 'export:mapped-product-to-erp';
    protected $description = 'Envio de dados mapeados de produto ao ERP.';

    public function handle()
    {
        $delayToJob = Carbon::now();

        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productRewrited')
            ->with('productRewrited')
            ->where('ai_adapted_the_content', true)
            ->orWhere('sku', 'PM04034081')
            ->get();

            //@TODO apagar esse SKU

        \Log::info("(SendMappedProductToErp) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $pending) {

            $delayToJob->addMinutes(rand(21, 123));

            SendMappedProductToErpJob::dispatch( $pending)
                                 ->delay($delayToJob);

           \Log::info("(SendMappedProductToErp) Job para item ".($pending->sku ?? 'sku')." para envio ao bling com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMappedProductToErp) Processo finalizado");

    }
}
