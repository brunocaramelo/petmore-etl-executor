<?php

namespace App\Actions\Bling;

use App\Models\{ProductCustomAttribute,
                ProductCategory};

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

            $actualGroups = $findLocaly->bling_group_field_identify ?? [];

            if (!in_array($params['category'], $actualGroups)) {

                $actualGroups[] = $params['category'];

                $actualGroupsFinal = collect($actualGroups)
                                            ->filter(function ($item) {
                                                return ProductCategory::where('bling_identify', $item)->exists();
                                            })->map(function ($value) {
                                                return ['id' => (int) $value];
                                            })->values()->toArray();

                $this->upsertAttributeInternal([
                    'action' => 'update',
                    'data' => [
                        'name' => $params['name'],
                        'agrupadores' => $actualGroupsFinal
                    ],
                    'entityInstance' => $findLocaly,
                    'consumerInstance' => $consumer,
                ]);

            }

            return [
                'slug' => $slugAttribute,
                'name' => $findLocaly->name,
                'bling_identify' => $findLocaly->bling_identify,
                'bling_group_field_identify' => $actualGroupsFinal,
            ];
        }

        $createdLocaly = $this->upsertAttributeInternal([
                    'action' => 'create',
                    'data' => [
                        'slug' => $slugAttribute,
                        'name' => $params['name'],
                        'agrupadores' => [
                            ['id' => (int) $params['category']]
                        ]
                    ],
                    'entityInstance' => $findLocaly,
                    'consumerInstance' => $consumer,
        ]);

        return [
            'slug' => $createdLocaly->slug,
            'name' => $createdLocaly->name,
            'bling_identify' => $createdLocaly->bling_identify,
            'bling_group_field_identify' => $createdLocaly->bling_group_field_identify,
        ];

    }

    private function upsertAttributeInternal($params)
    {
        $arrToupSert = [
                'nome' => $params['data']['name'],
                'situacao' => 1,
                'largura' => 2,
                'placeholder' => 'Informe o(a) '.strtolower($params['data']['name']),
                'obrigatorio' => false,
                'tipoCampo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.types.string'),
                ],
                'modulo' => [
                    'id' => config('custom-services.apis.bling_erp.settings.custom_fields.modules.default'),
                ],
                'agrupadores' => $params['data']['agrupadores'],

        ];


        if ($params['action'] === 'update') {

            $params['consumerInstance']->updateCustomField($params['entityInstance']->bling_identify, $arrToupSert)['data'];

            $params['entityInstance']->update(['bling_group_field' => $params['data']['agrupadores']]);

            return $params['entityInstance'];
        }

        $createdExternalField = $params['consumerInstance']->createCustomField($arrToupSert)['data'];

        return ProductCustomAttribute::create([
                'slug' => $params['data']['slug'],
                'name' => $params['data']['name'],
                'bling_identify' => $createdExternalField['id'],
                'bling_group_field' => $createdExternalField['idsVinculosAgrupadores'][0],
        ]);
    }



}
