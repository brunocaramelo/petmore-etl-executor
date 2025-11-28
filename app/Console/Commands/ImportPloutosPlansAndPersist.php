<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Imports\PloutosProductsPlanImport;
use Maatwebsite\Excel\Facades\Excel;

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

    private function importFromRemoteStorage()
    {
        $remoteFiles = array_filter(Storage::disk('choiced_cloud_storage')->files('petmore-public/import-plans'), function ($item) {
           return strpos($item, '.xlsx');
        });

        foreach ($remoteFiles as $planName) {
            Storage::disk('local')->put('import-plans-to-database/'.basename($planName),
                Storage::disk('choiced_cloud_storage')->get($planName)
            );
        }
    }

    private function cleanLocalStore()
    {
         $plansPloutos = array_filter(Storage::disk('local')->files('import-plans-to-database'), function ($item) {
            return strpos($item, '.xlsx');
         });

         foreach ($plansPloutos as $planName) {
            Storage::disk('local')->delete($planName);
        }
    }


    public function handle()
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') init');

        $this->cleanLocalStore();
        $this->importFromRemoteStorage();

        $plansPloutos = array_filter(Storage::disk('local')->files('import-plans-to-database'), function ($item) {
            return strpos($item, '.xlsx');
         });

        \Log::info(__CLASS__.' ('.__FUNCTION__.') importing plans: ', [
            'plans' => $plansPloutos
        ]);


         foreach ($plansPloutos as $planName) {

            $import = new PloutosProductsPlanImport();

            Excel::import($import,
            Storage::disk('local')->path($planName)
            );

            $import->persistData();
         }

        $this->cleanLocalStore();

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finished');
    }
}
