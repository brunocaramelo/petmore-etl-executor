<?php

namespace App\Resources;

use App\Actions\Bling\FindOrCreateCustomAttributeAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductToErpTransformResource extends JsonResource
{

    private function getUnidadeByParentProduct($param)
    {
        return match($param) {
            'UNIDADE' => 'UN',
        };
    }

    public function toArray($request): array
    {
        $parentProduct = $this;
        $preparedProduct = $this->productRewrited;

        $haveVariations = ((isset($preparedProduct->variations) && is_array($preparedProduct->variations) && count($preparedProduct->variations) > 0));

        $data = [
            "id" => null,
            "nome" => $preparedProduct->title ?? 'Produto sem Nome',
            "codigo" => $preparedProduct->sku,
            "preco" => (float) ($preparedProduct->price['current'] ?? 0),
            "tipo" => "P",
            "situacao" => "A",
            "formato" => ($haveVariations == true ? 'V' : 'S'),
            "descricaoCurta" => $preparedProduct->description['small']['html'] ?? ($preparedProduct->description['complement']['html'] ?? null),
            "dataValidade" => null,
            "unidade" => $this->getUnidadeByParentProduct($parentProduct->ploutos_unidade_de_medida),
            "pesoLiquido" => (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $preparedProduct->specifications[4]->rows[2]->value ?? '0'))),
            "pesoBruto" => (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $preparedProduct->specifications[4]->rows[2]->value ?? '0'))),
            "volumes" => 1,
            "itensPorCaixa" => 1,
            "gtin" => null, //@TODO- VER GTIN
            "gtinEmbalagem" => null,//@TODO- VER GTIN
            "tipoProducao" => "P",
            "condicao" => 1,
            "freteGratis" => false,
            "marca" => $parentProduct->ploutos_marca ?? 'Marca Desconhecida',
            "descricaoComplementar" => $preparedProduct->description['complement']['html'] ?? null,
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
            $data["camposCustomizados"] = collect($preparedProduct->specifications)->flatMap(function ($specification) use($parentProduct) {
                return collect($specification['rows'])->map(function ($row) use($parentProduct) {
                    $customFieldProcessed = app(FindOrCreateCustomAttributeAction::class)->execute([
                        'name' => $row['label'],
                        'category' =>  $parentProduct->category->bling_identify,
                    ]);

                    return [
                        "idCampoCustomizado" => $customFieldProcessed['bling_identify'],
                        "idVinculo" => $customFieldProcessed['bling_identify'],
                        "valor" => $row['value'],
                    ];
                });

            });
        }

        if ($haveVariations) {
            $data["variacoes"] = collect($preparedProduct->variations)->map(function ($variation, $index) use ($preparedProduct, $parentProduct) {

                $attributes = collect($variation['attributes'] ?? [])->map(function ($attr) {
                    return $attr[0]['label'] . ':' . (empty($attr[1]['value']) ? 'Padrão' : $attr[1]['value']);
                })->implode(';');

                $pesoUnidadeAttr = collect($variation['attributes'] ?? [])
                                    ->first(function($attr) {
                                        return ($attr['label'] ?? null) === 'Peso da unidade';
                                    });

                $pesoUnidade = $pesoUnidadeAttr ? (float) str_replace(',', '.', (preg_replace('/[^0-9,]/', '', $pesoUnidadeAttr[2]['value'] ?? '0'))) : 0;

                $skuVariation = $preparedProduct->sku.'-'.Str::slug(str_replace([':'] ,['-'], $attributes));

                return [
                    "nome" => $variation['title'] ?? '',
                    "codigo" => $skuVariation,
                    "preco" => (float) ($variation['price']['current'] ?? 0),
                    "tipo" => "P",
                    "situacao" => "A",
                    "formato" => "S",
                    "descricaoCurta" => $variation->description['small']['html'] ?? ($variation->description['complement']['html'] ?? null),
                    "dataValidade" => null,
                    "unidade" => "UN",
                    "pesoLiquido" => $pesoUnidade,
                    "pesoBruto" => $pesoUnidade,
                    "volumes" => 1,
                    "itensPorCaixa" => 1,
                    "gtin" => null,
                    "gtinEmbalagem" => null,
                    "tipoProducao" => "P",
                    "condicao" => 1,
                    "freteGratis" => false,
                    "marca" => $parentProduct->ploutos_marca ?? 'Marca Desconhecida',
                    "descricaoComplementar" => $variation->description['complement']['html'] ?? null,
                    "linkExterno" => $preparedProduct->url ?? '',
                    "observacoes" => null,
                    "descricaoEmbalagemDiscreta" => null,
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
                            "imagensURL" => collect($variation['images'] ?? [])->map(function ($image) {
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
                    "camposCustomizados" => collect($preparedProduct['specifications'])->flatMap(function ($specification) use($parentProduct) {
                            return collect($specification['rows'])->map(function ($row) use($parentProduct) {
                                $customFieldProcessed = app(FindOrCreateCustomAttributeAction::class)->execute([
                                    'name' => $row['label'],
                                    'category' =>  $parentProduct->category->bling_identify,
                                ]);

                                return [
                                    "idCampoCustomizado" => $customFieldProcessed['bling_identify'],
                                    "idVinculo" => $customFieldProcessed['bling_identify'],
                                    "valor" => $row['value'],
                                ];
                            });

                        })->filter()->values()->toArray(),
                    "variacao" => [
                        "nome" => $attributes,
                        "ordem" => $index + 1,
                        "produtoPai" => [
                            "cloneInfo" => false,
                        ]
                    ]
                ];
            })->toArray();
        }

        return $data;
    }
}
