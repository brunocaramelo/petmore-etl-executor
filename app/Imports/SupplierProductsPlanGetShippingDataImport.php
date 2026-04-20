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
    private $dataAux = [];
    private const PLAN_SKU_SEPARATOR = ',';
    private $needRoundToInt = false;

    public function collection(Collection $rows)
    {
        $translator = new ConfigRowProcessor($this->config);

        foreach ($rows as $index => $row) {
            $rowArr = $row->all();

            $identifyTranlated = $translator->getIdentifyValue($rowArr);

            $fieldsTranlated = $translator->processField($rowArr);
            $fieldsTranlated['local_sku'] = $identifyTranlated;

            $this->data[$index] = $fieldsTranlated;

            $this->persistDataSingle($fieldsTranlated);
        }
    }

    private function doRoundToInt(float|string|int|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($this->needRoundToInt === true) {
            return (int) round($value);
        }

        return (int) $value;
    }

    private function doFloatMoney(float|string|null $value): ?float
    {
        if (empty($value)) {
            return null;
        }

        if (is_float($value)) {
            return $value;
        }

        $cleanValue = preg_replace('/[^0-9.,]/', '', $value);

        if (str_contains($cleanValue, ',') && str_contains($cleanValue, '.')) {
            $cleanValue = str_replace('.', '', $cleanValue);
            $cleanValue = str_replace(',', '.', $cleanValue);
        } elseif (str_contains($cleanValue, ',')) {
            $cleanValue = str_replace(',', '.', $cleanValue);
        }

        return (float) $cleanValue;
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
                                                  ->where('can_update', true)
                                                  ->get();

            if ($updateDatas->isEmpty()){
                continue;
            }

            foreach ($updateDatas as $updateData) {

                \Log::info('Produto Localizado, preparando atualizacao :', $row);

                $choicedValuesToUpdate = $this->choiceUpdateData($row, $updateData);

                $updateData->update($choicedValuesToUpdate);

            }
        }
    }

    public function persistDataSingle($row)
    {
            $updateDatas = ProductSelfCommerceData::whereIn('sku', explode(self::PLAN_SKU_SEPARATOR, $row['local_sku']) ?? ['NENHUM_SKU'] )
                                                  ->where('can_update', true)
                                                  ->get();

            if ($updateDatas->isEmpty()){
                return false;
            }

            foreach ($updateDatas as $updateData) {
                \Log::info('Produto Localizado, preparando atualizacao :', $row);
                $choicedValuesToUpdate =$this->choiceUpdateData($row, $updateData);
                //dd(' before update numero um', $choicedValuesToUpdate);

                $updateData->update($choicedValuesToUpdate);
            }

        return true;
    }

    public function handle()
    {
        $this->setConfig();
    }

    public function setNeedRoundToInt(bool $condition)
    {
        $this->needRoundToInt = $condition;
    }


    private function setConfig()
    {
        $configFilPath = base_path('app/Console/Commands/PlanImportEan/config.json');
        $this->config = json_decode(file_get_contents($configFilPath), true);
    }

    private function preferOriginData($new, $old)
    {
        return !empty($new) ? $new : (!empty($old) ? $old : null);
    }

    private function choiceUpdateData($row, $updateData)
    {
        $markupFiltered = filter_var($row['seller_markup'], FILTER_VALIDATE_FLOAT) !== false && $row['seller_markup'] < 1
        ? $row['seller_markup'] * 100
        : $row['seller_markup'];

        $supplierDescontoPercentualFiltered = filter_var($row['supplier_desconto_percentual'], FILTER_VALIDATE_FLOAT) !== false && $row['supplier_desconto_percentual'] < 1
        ? $row['supplier_desconto_percentual'] * 100
        : $row['supplier_desconto_percentual'];

        $ean = $this->preferOriginData($row['ean'] ?? null, $updateData->ean ?? null);
        $height = $this->preferOriginData($this->doRoundToInt($row['height'] ?? null), $updateData->height ?? null);
        $length = $this->preferOriginData($this->doRoundToInt($row['length'] ?? null), $updateData->length ?? null);
        $weight = $this->preferOriginData($row['weight'] ?? null, $updateData->weight ?? null);
        $width = $this->preferOriginData($this->doRoundToInt($row['width'] ?? null), $updateData->width ?? null);

        $externalSupplierProductDescription = $this->preferOriginData(
            $row['supplier_product_description'] ?? null,
            $updateData->external_supplier_product_description ?? null
        );

        $externalSupplierName = $this->preferOriginData(
            $row['supplier_name'] ?? null,
            $updateData->external_supplier_name ?? null
        );

        $externalSupplierSku = $this->preferOriginData(
            $row['supplier_sku'] ?? null,
            $updateData->external_supplier_sku ?? null
        );

        $supplierPrecoPadrao = $this->preferOriginData($this->doFloatMoney($row['supplier_preco_padrao'] ?? null), $updateData->supplier_preco_padrao ?? null);
        $supplierDescontoPercentual = $this->preferOriginData($this->doRoundToInt($supplierDescontoPercentualFiltered ?? null), $updateData->supplier_desconto_percentual ?? null);
        $supplierValorFinal = $this->preferOriginData($this->doFloatMoney($row['supplier_valor_final'] ?? null), $updateData->supplier_valor_final ?? null);
        $sellerSugestaoVenda = $this->preferOriginData($this->doFloatMoney($row['seller_sugestao_venda'] ?? null), $updateData->seller_sugestao_venda ?? null);
        $sellerMarkup = $this->preferOriginData($this->doRoundToInt($markupFiltered ?? null), $updateData->seller_markup ?? null);

        return [
            'has_searched' => true,
            'ean' => $ean,
            'height' => $height,
            'length' => $length,
            'weight' => $weight,
            'width' => $width,
            'external_supplier_product_description' => $externalSupplierProductDescription,
            'external_supplier_name' => $externalSupplierName,
            'external_supplier_sku' => $externalSupplierSku,
            'supplier_preco_padrao' => $supplierPrecoPadrao,
            'supplier_desconto_percentual' => $supplierDescontoPercentual,
            'supplier_valor_final' => $supplierValorFinal,
            'seller_sugestao_venda' => $sellerSugestaoVenda,
            'seller_markup' => $sellerMarkup,
        ];

    }

}
