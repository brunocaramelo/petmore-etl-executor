<?php

namespace App\Console\Commands\PlanImportEan;

use Illuminate\Console\Command;

use App\Imports\SupplierProductsPlanGetShippingDataImport;

use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use App\Models\ProductSelfCommerceData;

use Illuminate\Support\Facades\Artisan;
class PlanImportEanAndShippingDataToSelfCommerce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:plan-import-ean-and-shipping-data-to-self-commerce {--send_my_app=no} {--just_send_my_app=no}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private function importFromRemoteStorage()
    {
        $remoteFiles = array_filter(Storage::disk('choiced_cloud_storage')->files('petmore-public/import-plans/update-shipping-data'), function ($item) {
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

        $this->importPlans($plansPloutos);

        $this->sendToEMyApp();

        $this->cleanLocalStore();

        \Log::info(__CLASS__.' ('.__FUNCTION__.') finished');
    }

    private function importPlans($plansPloutos): bool
    {
        if ($this->option('just_send_my_app') == 'no') {
            return false;
        }

        foreach ($plansPloutos as $planName) {
            $import = new SupplierProductsPlanGetShippingDataImport();
            $import->handle();

            Excel::import($import, Storage::disk('local')->path($planName));

            $import->persistData();
         }

        return true;
    }

    private function sendToEMyApp(): bool
    {
        if ($this->option('send_my_app') !== 'yes' || $this->option('just_send_my_app') !== 'yes') {
            return false;
        }

        Artisan::call('maintain:prepare-changes-product-to-self-ecommerce-tool-to-hub-integration');

        return true;
    }

}
