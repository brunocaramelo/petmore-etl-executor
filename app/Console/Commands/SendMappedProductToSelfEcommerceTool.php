<?php

namespace App\Console\Commands;

use App\Models\ProductCentral;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Jobs\SendMappedProductToSelfEcommerceJob;

class SendMappedProductToSelfEcommerceTool extends Command
{

    protected $signature = 'export:mapped-product-to-self-ecommerce-tool {--forced_list_skus=}';
    protected $description = 'Envio de dados mapeados de produto ao Ecommerce proprio.';

    public function handle()
    {
        $skusForcedByParam = explode(',', $this->option('forced_list_skus'));

        $delayToJob = Carbon::now();

        $delayMinutesJobMin = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.min');
        $delayMinutesJobMax = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.max');
        $queueJobName = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.queue');

        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productRewrited')
            ->with('productRewrited')
            ->when( empty($skusForcedByParam), function ($query) {
                return $query->where('ai_adapted_the_content', true)
                            ->where('synced_self_ecommerce', false);
            })->when( !empty($skusForcedByParam), function ($query) use ($skusForcedByParam) {
                return $query->whereHas('productRewrited', function ($q) use ($skusForcedByParam) {
                     $q->whereIn('sku', $skusForcedByParam);
                });
            })
            ->get();

        \Log::info("(SendMappedProductToSelfEcommerceTool) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $indexPending => $pending) {
            if ($indexPending > 0) {
                $delayToJob->addMinutes(rand($delayMinutesJobMin, $delayMinutesJobMax));
            }

            SendMappedProductToSelfEcommerceJob::dispatch( $pending)
                                ->onQueue($queueJobName)
                                ->delay($delayToJob);

           \Log::info("(SendMappedProductToSelfEcommerceTool) Job para item ".($pending->sku ?? 'sku')." para envio ao ecommerce com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMappedProductToSelfEcommerceTool) Processo finalizado");

    }
}
