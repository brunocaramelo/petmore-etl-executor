<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class ImportPloutosPlansAndPersist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ploutos-plans-and-persist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Etapa 1 - Importação de planilhas de produtos do Ploutos para Persistir no sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plansPloutos = array_filter(\Storage::disk('local')->files('ploutos-plans'), function ($item) {
            return strpos($item, '.xlsx');
         });

         foreach ($plansPloutos as $planName) {

            $import = new \App\Imports\PloutosProductsPlanImport();

            \Maatwebsite\Excel\Facades\Excel::import($import,
                                            \Storage::disk('local')->path($planName)
                                        );

            $import->persistData();


         }
    }
}
