<?php

namespace App\Actions\Bling;

use App\Models\ProductCustomAttribute;

use App\Consumers\{BlingErpConsumer,
                   BlingOauthConsumer};

use Illuminate\Support\Str;

class FindOrCreateCustomAttributeAction
{
    public function execute(array $params)
    {
        $slugAttribute = Str::slug($params['name']);

        $findLocaly = ProductCustomAttribute::where('slug', $slugAttribute)->first();

        if ($findLocaly instanceof ProductCustomAttribute) {
            return [
                'slug' => $slugAttribute,
                'name' => $findLocaly->name,
                'bling_identify' => $findLocaly->bling_identify,
                'bling_group_field_identify' => $findLocaly->bling_group_field_identify,
            ];
        }

        $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
            'auto_login' => true,
            'base_path' => config('custom-services.apis.bling_erp.base_path'),
        ]);

        $createdExternalField = $consumer->createCustomField([
                'nome' => $params['name'],
                'situacao' => 1,
                'largura' => 2,
                'placeholder' => 'Informe o(a) '.strtolower($params['name']),
                'obrigatorio' => false,
                'tipoCampo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.types.string'),
                ],
                'agrupadores' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.groupers.default'),
                ],
                'modulo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.modules.default'),
                ],
        ]);

        $createdLocaly = ProductCustomAttribute::create([
                'slug' => $slugAttribute,
                'name' => $params['name'],
                'bling_identify' => $createdExternalField->id,
                'bling_group_field_identify' => $createdExternalField->idsVinculosAgrupadores[0],
        ]);

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'bling_identify' => $createdLocaly->bling_identify,
            'bling_group_field_identify' => $createdLocaly->bling_group_field_identify,
        ];

    }
}
