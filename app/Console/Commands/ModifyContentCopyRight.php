<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ProductCentral;

use App\Actions\CreateRewritedProductAction;

use App\Jobs\CreateContentWithoutCopyrightJob;

use Carbon\Carbon;

class ModifyContentCopyRight extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:modified-content-copy-right';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alterando o conteudo antes de enviar ao ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $delayToJob = Carbon::now();

        $pendingItems = ProductCentral::where('synced_ml', true)
            ->where('is_active', true)
            ->whereNotNull('url_product_ml')
            ->has('productMl')
            ->where('ai_adapted_the_content', false)
            ->get();


        \Log::info("(ModifyContentCopyRight) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $indexPending => $pending) {
            if ($indexPending > 0) {
                $delayToJob->addMinutes(rand(10, 22));
            }

            CreateContentWithoutCopyrightJob::dispatch(new CreateRewritedProductAction(), $pending)
                                 ->delay($delayToJob);

           \Log::info("(ModifyContentCopyRight) Job para item ".($pending->sku ?? 'sku')." de busca no conteudo para copy right free despachado com atraso para: " . $delayToJob);

        }
        \Log::info("(ModifyContentCopyRight) Processo finalizado");
    }
}
