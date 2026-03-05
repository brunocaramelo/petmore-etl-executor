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

        $delayMinutesJobMin = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.min');
        $delayMinutesJobMax = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.max');
        $queueJobName = config('custom-services.jobs_intervals.minutes.send-self-ecommerce.queue');

        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productRewrited')
            ->with('productRewrited')
            ->where('ai_adapted_the_content', true)
            // ->where('synced_self_ecommerce', false)
            ->whereHas('productRewrited', function ($query) {
                $query->whereIn('sku', ["PM00006840",
                "PM00006842",
                "PM00006962",
                "PM00006963",
                "PM00006964",
                "PM00006965",
                "PM00006966",
                "PM00006967",
                "PM00006968",
                "PM00006969",
                "PM00006970",
                "PM00006971",
                "PM00007006",
                "PM00007007",
                "PM00007008",
                "PM00007009",
                "PM00007011",
                "PM00007012",
                "PM00007377",
                "PM00007380",
                "PM00007977",
                "PM00008101",
                "PM00008119",
                "PM00008445",
                "PM00008446",
                "PM00008846",
                "PM00008847",
                "PM00008848",
                "PM00008849",
                "PM00009060",
                "PM00009122",
                "PM00009692",
                "PM00010767",
                "PM00011125",
                "PM00011127",
                "PM00011128",
                "PM00011176",
                "PM00011492",
                "PM00011493",
                "PM00011494",
                "PM00011495",
                "PM00011496",
                "PM00011497",
                "PM00011539",
                "PM00011541",
                "PM00011660",
                "PM00011661",
                "PM00011727",
                "PM00011730",
                "PM00012268",
                "PM00012269",
                "PM00012270",
                "PM00012727",
                "PM00013130",
                "PM00013131",
                "PM00013179",
                "PM00013180",
                "PM00013181",
                "PM00013182",
                "PM00013183",
                "PM00013464",
                "PM00013991",
                "PM00013992",
                "PM00013994",
                "PM00014432",
                "PM00014433",
                "PM00014434",
                "PM00014435",
                "PM00014436",
                "PM00014723",
                "PM00014853",
                "PM00014854",
                "PM00014887",
                "PM00014888",
                "PM00014889",
                "PM00014890",
                "PM00014891",
                "PM00015531",
                "PM001W4A59",
                "PM00218437",
                "PM00218438",
                "PM00218439",
                "PM00218440",
                "PM00218442",
                "PM00218443",
                "PM00218444",
                "PM00218445",
                "PM00218447",
                "PM00218449",
                "PM00218450",
                "PM00218451",
                "PM00218453",
                "PM00218454",
                "PM00218455",
                "PM00218456",
                "PM00218457",
                "PM00218458",
                "PM00218459",
                "PM00218461",
                "PM00218462",
                "PM04024012",
                "PMJB70111N",
                "PMLQ9QNUXFFE"
                ]);
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
