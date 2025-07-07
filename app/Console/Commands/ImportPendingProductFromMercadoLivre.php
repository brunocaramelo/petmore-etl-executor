<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ProductCentral;

use Carbon\Carbon;

use App\Jobs\MercadoLivreImportProductByUriAndAttachToProductCentralJob;

class ImportPendingProductFromMercadoLivre extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:pending-product-from-mercado-livre';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importando produtos pendentes do Mercado Livre';


    public function handle()
    {

        $pendingItems = ProductCentral::where('synced_ml', false)
                                        ->where('is_active', true)
                                        ->whereNull('product_ml_id')
                                        ->whereNotNull('url_product_ml')
                                        ->get();

        \Log::info("(ImportPendingProductFromMercadoLivre) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $pending) {

            $delayToJob = Carbon::now()->addSeconds(rand(20,49));

            MercadoLivreImportProductByUriAndAttachToProductCentralJob::dispatch($pending)
                                 ->delay($delayToJob);

            \Log::info("(ImportPendingProductFromMercadoLivre) Job para item ".($pending->sku ?? 'sku')." de busca no mercado livre despachado com atraso para: " . $delayToJob." , para o produto: ".($pending->url_product_ml ?? 'URL'));

        }
    }
}
