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

        $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
            'auto_login' => true,
            'base_path' => config('custom-services.apis.bling_erp.base_path'),
        ]);

        if ($findLocaly instanceof ProductCustomAttribute) {
            $actualGroups = $findLocaly->bling_group_field ?? [];

            if (!in_array($params['category'], $actualGroups)) {

                $actualGroups[] = $params['category'];

                $listItemsToUpdate = collect($actualGroups)->map(function ($value) {
                    return ['id' => $value];
                });

                 $updatedExternalField = $consumer->updateCustomField($findLocaly->bling_identify, [
                    'nome' => $params['name'],
                    'situacao' => 1,
                    'largura' => 2,
                    'placeholder' => 'Informe o(a) '.strtolower($params['name']),
                    'obrigatorio' => false,
                    'tipoCampo' => [
                        'id' => config('custom-services.apis.bling_erp.settings.custom_fields.types.string'),
                    ],
                    'modulo' => [
                        'id' => config('custom-services.apis.bling_erp.settings.custom_fields.modules.default'),
                    ],
                    'agrupadores' => [
                        $listItemsToUpdate,
                    ],
                ])['data'];

                $findLocaly->update(['bling_group_field' => $listItemsToUpdate]);
            }

            return [
                'slug' => $slugAttribute,
                'name' => $findLocaly->name,
                'bling_identify' => $findLocaly->bling_identify,
                'bling_group_field_identify' => $actualGroups,
            ];
        }

        $createdExternalField = $consumer->createCustomField([
                'nome' => $params['name'],
                'situacao' => 1,
                'largura' => 2,
                'placeholder' => 'Informe o(a) '.strtolower($params['name']),
                'obrigatorio' => false,
                'tipoCampo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.types.string'),
                ],
                'modulo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.modules.default'),
                ],
                'agrupadores' => [
                    ['id' => $params['category']],
                ],
        ])['data'];

        $createdLocaly = ProductCustomAttribute::create([
                'slug' => $slugAttribute,
                'name' => $params['name'],
                'bling_identify' => $createdExternalField['id'],
                'bling_group_field' => $createdExternalField['idsVinculosAgrupadores'][0],
        ]);

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'bling_identify' => $createdLocaly->bling_identify,
            '' => $createdLocaly->bling_group_field_identify,
        ];

    }
}
