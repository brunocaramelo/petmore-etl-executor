<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ProductSelfCommerceData;

use App\Actions\SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction;

use App\Jobs\SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlJob;

use Carbon\Carbon;

class SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:self-ecommerce-official-data-to-marketplace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buscando dados oficiais dos produtos para gravacao';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // die($this->loadFileLocal());

        $delayToJob = Carbon::now();

        $pendingItems = ProductSelfCommerceData::where('TYPE','simple')
        ->limit(1)
        ->get();

        \Log::info("(SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlConsole) Itens pendentes encontrados para serem processados ".$pendingItems->count());

        foreach ($pendingItems as $indexPending => $pending) {
            if ($indexPending > 0) {
                $delayToJob->addMinutes(rand(10, 22));
            }

            SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlJob::dispatch(new SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction(), $pending)
                                 ->delay($delayToJob);

           \Log::info("(SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlConsole) Job para item ".($pending->sku ?? 'sku')." de busca no conteudo para copy right free despachado com atraso para: " . $delayToJob);

        }
        \Log::info("(SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlConsole) Processo finalizado");
    }

    private function loadFileLocal()
    {
        $arrFile = json_decode('', 1);

        foreach ($arrFile as $item) {
            unset($item['uuid']);
            // dd($item);
            $ent = new ProductSelfCommerceData($item);
            $ent->save();

        }
    }


}


