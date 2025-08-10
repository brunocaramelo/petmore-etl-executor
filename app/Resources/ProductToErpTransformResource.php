<?php

namespace App\Resources;

use App\Actions\Bling\FindOrCreateCustomAttributeAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductToErpTransformResource extends JsonResource
{

    private function getUnidadeByParentProduct($param)
    {
        return match($param) {
            'UNIDADE' => 'UN',
            // 'UNIDADE' => 'UN',
            // 'UNIDADE' => 'UN',
        };
    }

    public function toArray($request): array
    {
        $parentProduct = $this;
        $preparedProduct = $this->productRewrited;

        $data = [
            "id" => null,
            "nome" => $preparedProduct->title ?? 'Produto sem Nome',
            "codigo" => null,
            "preco" => (float) ($preparedProduct->price['current'] ?? 0),
            "tipo" => "P",
            "situacao" => "A",
            "formato" => "S",
            "descricaoCurta" => substr($preparedProduct->description['text'] ?? '', 0, 255),
            "dataValidade" => null,
            "unidade" => $this->getUnidadeByParentProduct($parentProduct->ploutos_unidade_de_medida),
            "pesoLiquido" => (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $preparedProduct->specifications[4]->rows[2]->value ?? '0'))),
            "pesoBruto" => (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $preparedProduct->specifications[4]->rows[2]->value ?? '0'))),
            "volumes" => 1,
            "itensPorCaixa" => 1,
            "gtin" => null, //@TODO- VER GTIN
            "gtinEmbalagem" => null,//@TODO- VER GTIN
            "tipoProducao" => "P",
            "condicao" => 0,
            "freteGratis" => false,
            "marca" => $parentProduct->ploutos_marca ?? 'Marca Desconhecida',
            "descricaoComplementar" => $preparedProduct->description['html'] ?? '',
            // "linkExterno" => $preparedProduct->url ?? '',
            "observacoes" => null,
            "descricaoEmbalagemDiscreta" => null,
            "categoria" => [
                "id" => $parentProduct->category->bling_identify
            ],
            "estoque" => [
                "minimo" => 1,
                "maximo" => 10000,
                "crossdocking" => 1,
                "localizacao" => "14A",
            ],
            "actionEstoque" => "",
            "dimensoes" => [
                "largura" => 1,
                "altura" => 1,
                "profundidade" => 1,
                "unidadeMedida" => 1,
            ],
            "tributacao" => [
                "origem" => 0,
                "nFCI" => "",
                "ncm" => "",
                "cest" => "",
                "codigoListaServicos" => "",
                "spedTipoItem" => "",
                "codigoItem" => "",
                "percentualTributos" => 0,
                "valorBaseStRetencao" => 0,
                "valorStRetencao" => 0,
                "valorICMSSubstituto" => 0,
                "codigoExcecaoTipi" => "",
                "classeEnquadramentoIpi" => "",
                "valorIpiFixo" => 0,
                "codigoSeloIpi" => "",
                "valorPisFixo" => 0,
                "valorCofinsFixo" => 0,
                "codigoANP" => "",
                "descricaoANP" => "",
                "percentualGLP" => 0,
                "percentualGasNacional" => 0,
                "percentualGasImportado" => 0,
                "valorPartida" => 0,
                "tipoArmamento" => 0,
                "descricaoCompletaArmamento" => "",
                "dadosAdicionais" => "",
            ],
            "midia" => [
                "imagens" => [
                    "imagensURL" => collect($preparedProduct->images ?? [])->map(function ($image) {
                        return [
                            "link" => $image['full_size'] ?? $image['mid_size'] ?? $image['thumbnail'] ?? null
                        ];
                    })->filter()->values()->toArray()
                ]
            ],
            "linhaProduto" => [
                "id" => 1,
            ],
            "estrutura" => [
                "tipoEstoque" => "F",
                "lancamentoEstoque" => "A",
                "componentes" => [
                    [
                        "produto" => ["id" => 1],
                        "quantidade" => 2.1,
                    ]
                ]
            ],
        ];

        if (isset($preparedProduct->specifications) && is_array($preparedProduct->specifications)) {
            $data["camposCustomizados"] = collect($preparedProduct->specifications)->flatMap(function ($specification) {
                return collect($specification['rows'])->map(function ($row) {
                    $customFieldProcessed = app(FindOrCreateCustomAttributeAction::class)->execute([
                        'name' => $row['label']
                    ]);

                    return [
                        "idCampoCustomizado" => $customFieldProcessed['bling_identify'],
                        "idVinculo" => $customFieldProcessed['bling_identify'],
                        "valor" => $row['value'],
                    ];
                });

            });

        if (isset($preparedProduct->variations) && is_array($preparedProduct->variations)) {
            $data["variacoes"] = collect($preparedProduct->variations)->map(function ($variation, $index) use ($preparedProduct, $parentProduct) {
                $attributes = collect($variation->attributes ?? [])->map(function ($attr) {
                    return $attr->label . ':' . $attr[2]->value;
                })->implode(';');

                $pesoUnidadeAttr = collect($variation->attributes ?? [])
                                    ->first(function($attr) {
                                        return ($attr->label ?? null) === 'Peso da unidade';
                                    });
                $pesoUnidade = $pesoUnidadeAttr ? (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $pesoUnidadeAttr[2]->value ?? '0'))) : 0;

                return [
                    "nome" => $variation->title ?? '',
                    "codigo" => null,
                    "preco" => (float) ($variation->price['current'] ?? 0),
                    "tipo" => "P",
                    "situacao" => "A",
                    "formato" => "S",
                    "descricaoCurta" => substr($variation->title ?? '', 0, 255),
                    "dataValidade" => "2020-01-01",
                    "unidade" => "UN",
                    "pesoLiquido" => $pesoUnidade,
                    "pesoBruto" => $pesoUnidade,
                    "volumes" => 1,
                    "itensPorCaixa" => 1,
                    "gtin" => "1234567890123",
                    "gtinEmbalagem" => "1234567890123",
                    "tipoProducao" => "P",
                    "condicao" => 0,
                    "freteGratis" => false,
                    "marca" => $parentProduct->ploutos_marca ?? 'Marca Desconhecida',
                    "descricaoComplementar" => $preparedProduct->description->html ?? '',
                    "linkExterno" => $preparedProduct->url ?? '',
                    "observacoes" => null,
                    "descricaoEmbalagemDiscreta" => "Produto teste",
                    "categoria" => [
                        "id" => $parentProduct->category->bling_identify,
                    ],
                    "estoque" => [
                        "minimo" => 1,
                        "maximo" => 100,
                        "crossdocking" => 1,
                        "localizacao" => "14A",
                    ],
                    "actionEstoque" => "",
                    "dimensoes" => [
                        "largura" => 1,
                        "altura" => 1,
                        "profundidade" => 1,
                        "unidadeMedida" => 1,
                    ],
                    "tributacao" => [
                        "origem" => 0,
                        "nFCI" => "",
                        "ncm" => "",
                        "cest" => "",
                        "codigoListaServicos" => "",
                        "spedTipoItem" => "",
                        "codigoItem" => "",
                        "percentualTributos" => 0,
                        "valorBaseStRetencao" => 0,
                        "valorStRetencao" => 0,
                        "valorICMSSubstituto" => 0,
                        "codigoExcecaoTipi" => "",
                        "classeEnquadramentoIpi" => "",
                        "valorIpiFixo" => 0,
                        "codigoSeloIpi" => "",
                        "valorPisFixo" => 0,
                        "valorCofinsFixo" => 0,
                        "codigoANP" => "",
                        "descricaoANP" => "",
                        "percentualGLP" => 0,
                        "percentualGasNacional" => 0,
                        "percentualGasImportado" => 0,
                        "valorPartida" => 0,
                        "tipoArmamento" => 0,
                        "descricaoCompletaArmamento" => "",
                        "dadosAdicionais" => "",
                    ],
                    "midia" => [
                        "imagens" => [
                            "imagensURL" => collect($variation->images ?? [])->map(function ($image) {
                                return [
                                    "link" => $image['full_size'] ?? $image['mid_size'] ?? $image['thumbnail'] ?? null
                                ];
                            })->filter()->values()->toArray()
                        ]
                    ],
                    "linhaProduto" => [
                        "id" => 1,
                    ],
                    "estrutura" => [
                        "tipoEstoque" => "F",
                        "lancamentoEstoque" => "A",
                        "componentes" => [
                            [
                                "produto" => ["id" => 1],
                                "quantidade" => 2.1,
                            ]
                        ]
                    ],
                    "camposCustomizados" => collect($preparedProduct->specifications)->flatMap(function ($specification) {
                            return collect($specification['rows'])->map(function ($row) {
                                $customFieldProcessed = app(FindOrCreateCustomAttributeAction::class)->execute([
                                    'name' => $row['label']
                                ]);

                                return [
                                    "idCampoCustomizado" => $customFieldProcessed['bling_identify'],
                                    "idVinculo" => $customFieldProcessed['bling_identify'],
                                    "valor" => $row['value'],
                                ];
                            });

                        })->filter()->values()->toArray()
                    ,
                    "variacao" => [
                        "nome" => $attributes,
                        "ordem" => $index + 1,
                        "produtoPai" => [
                            "cloneInfo" => true,
                        ]
                    ]
                ];
            })->toArray();
            }

            return $data;
        }
    }
}
