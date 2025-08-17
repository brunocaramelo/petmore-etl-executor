<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Carbon\Carbon;

use App\Casts\ValueCast;

use App\Models\ProductCentral;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class PloutosProductsPlanImport implements ToCollection, WithHeadingRow
{
    private $data = [];

    public function collection(Collection $rows)
    {

        foreach ($rows as $row) {

            if(! $this->checkIsNotEmptyRow($row)) continue;

            $this->data[] =  [
                "ploutos_cod" => $row["cod"],
                "ploutos_cod_barras" => $row["cod_barras"],
                "ploutos_descricao" => $row["descricao"],
                "ploutos_categoria" => $row["categoria"],
                "ploutos_marca" => $row["marca"],
                "ploutos_custo_tabela" => $row["custo_tabela"],
                "ploutos_estoque_minimo_ui" => $row["estoque_minimo_ui"],
                "ploutos_saldo_ui" => $row["saldo_ui"],
                "ploutos_estoque_atual_ui" => $row["estoque_atual_ui"],
                "ploutos_unidade_de_medida" => $row["unidade_de_medida"],
                "ploutos_custo_medio_ui" => $row["custo_medio_ui"],
                "ploutos_preco_uso" => $row["preco_uso"],
                "ploutos_estoque_minimo_rv" => $row["estoque_minimo_rv"],
                "ploutos_saldo_rv" => $row["saldo_rv"],
                "ploutos_estoque_atual_rv" => $row["estoque_atual_rv"],
                "ploutos_custo_medio_rv" => $row["custo_medio_rv"],
                "ploutos_preco_venda" => $row["preco_venda"],
                "url_product_ml" => $row["url_product_ml"],
              ];
        }
    }

    private function checkIsNotEmptyRow($row)
    {
        return (
            !empty($row['cod'])
            && !empty($row['url_product_ml'])
        );
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
        foreach($this->data as $toSave) {
            $this->persistRow($toSave);
        }
    }


    private function persistRow(array $row)
    {
        $dataExists = true;

        $inst = ProductCentral::where('ploutos_cod', $row['ploutos_cod'])
                                ->first();

        $dataExists = ($inst instanceof ProductCentral);

        $codePloutosClean = str_replace(['.','/',' '],
                                        ['','',''],
                                        $row['ploutos_cod']
                                    );

        $row['sku'] = $inst->sku ?? 'PM'.str_pad($codePloutosClean, 8, "0", STR_PAD_LEFT);
        $row['is_to_sell'] = $inst->is_to_sell ?? true;
        $row['is_active'] =  $inst->is_active ?? true;
        $row['synced_erp'] =  $inst->synced_erp ?? false;
        $row['synced_ml'] =  $inst->synced_ml ?? false;
        $row['ai_adapted_the_content'] =  $inst->ai_adapted_the_content ?? false;
        $row['category_id'] =  ProductCategory::where(
                                                        'slug',
                                                        Str::slug($row['ploutos_categoria'])
                                                    )->first()->uuid ?? null;

        $row = $this->castValuesArray($row);

        if (!$dataExists) {
            return ProductCentral::create($row);
        }

        $inst->update($row);

        return $inst;

    }

    private function castValuesArray($row)
    {
        $castValue = new ValueCast();

        $row['ploutos_custo_tabela'] = $castValue->castValue($row['ploutos_custo_tabela'] , 'decimal:2');
        $row['ploutos_estoque_minimo_rv'] = $castValue->castValue($row['ploutos_estoque_minimo_rv'] , 'integer');
        $row['ploutos_estoque_atual_rv'] = $castValue->castValue($row['ploutos_estoque_atual_rv'] , 'integer');
        $row['ploutos_saldo_rv'] = $castValue->castValue($row['ploutos_saldo_rv'] , 'decimal:2');
        $row['ploutos_custo_medio_rv'] = $castValue->castValue($row['ploutos_custo_medio_rv'] , 'decimal:2');
        $row['ploutos_preco_venda'] = $castValue->castValue($row['ploutos_preco_venda'] , 'decimal:2');

        $row['is_active'] = $castValue->castValue($row['is_active'] , 'boolean');
        $row['is_to_sell'] = $castValue->castValue($row['is_to_sell'] , 'boolean');
        $row['synced_erp'] = $castValue->castValue($row['synced_erp'] , 'boolean');
        $row['synced_ml'] = $castValue->castValue($row['synced_ml'] , 'boolean');
        $row['ai_adapted_the_content'] = $castValue->castValue($row['ai_adapted_the_content'] , 'boolean');

       return $row;
    }

}
