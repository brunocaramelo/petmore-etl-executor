<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Casts\ConfigRowProcessor;
use App\Casts\ValueCast;

use Illuminate\Support\Str;

class SupplierProductsPlanGetShippingDataImport implements ToCollection, WithHeadingRow
{
    private $config = [];
    private $data = [];

    public function collection(Collection $rows)
    {
        $translator = new ConfigRowProcessor($this->config);

        foreach ($rows as $index => $row) {

            $rowArr = $row->all();

            $identifyTranlated = $translator->getIdentifyValue($rowArr);
            $fieldsTranlated = $translator->processField($rowArr);

            $this->data[$index] = $fieldsTranlated;
            $this->data[$index]['local_sku'] = $identifyTranlated;

            if($index < 1) continue;
            dd($identifyTranlated, $fieldsTranlated);

        }
    }


    public function headingRow(): int
    {
        return 1;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function persistData()
    {
        return $this->data;
    }

    public function handle()
    {
        $this->setConfig();
    }


    private function setConfig()
    {
        $configFilPath = base_path('app/Console/Commands/PlanImportEan/config.json');
        $this->config = json_decode(file_get_contents($configFilPath), true);
    }

}
