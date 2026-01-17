<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Casts\ConfigRowProcessor;
use App\Casts\ValueCast;
use App\Models\ProductSelfCommerceData;

class SupplierProductsPlanGetShippingDataImport implements ToCollection, WithHeadingRow
{
    private $config = [];
    private $data = [];
    private const PLAN_SKU_SEPARATOR = ';';

    public function collection(Collection $rows)
    {
        $translator = new ConfigRowProcessor($this->config);

        foreach ($rows as $index => $row) {

            $rowArr = $row->all();

            $identifyTranlated = $translator->getIdentifyValue($rowArr);
            $fieldsTranlated = $translator->processField($rowArr);

            $this->data[$index] = $fieldsTranlated;
            $this->data[$index]['local_sku'] = $identifyTranlated;

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
        foreach ($this->data as $row) {

            $updateDatas = ProductSelfCommerceData::whereIn('sku', explode(self::PLAN_SKU_SEPARATOR, $row['local_sku']) ?? ['NENHUM_SKU'] )
                                                  ->where('has_searched', false)
                                                  ->get();

            if ($updateDatas->isEmpty()){
                continue;
            }

            foreach ($updateDatas as $updateData) {

                \Log::info('Produto Localizado, preparando atualizacao :', $row);

                $updateData->update([
                    'has_searched' => true,
                    'ean' => $row['ean'],
                    'height' => $row['height'],
                    'length' => $row['length'],
                    'weight' => $row['weight'],
                    'width' => $row['width'],
                    'external_supplier_product_description' => $row['supplier_product_description'],
                    'external_supplier_name' => $row['supplier_name'],
                    'external_supplier_sku' => $row['supplier_sku'],
                ]);
            }
        }
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
