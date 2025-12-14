<?php

namespace App\Actions;

use App\Models\{ProductCentral,
                ProductSelfCommerceData
                };

use App\Consumers\AiApiConsumer;
use Illuminate\Support\Facades\{Storage,
                                Http
                            };
use Exception;

class SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction
{
    public function execute(ProductSelfCommerceData $instance)
    {
        $this->configureForHeavyOperations();

        $skuToFind = empty($instance->parent_sku) ? $instance->sku : $instance->parent_sku;
        $instanceCentral = ProductCentral::where('sku', $skuToFind)->first();

        \Log::info(__CLASS__.' ('.__FUNCTION__.') starting proccess to', [
            'sku' => $instance->sku,
            'title' => $instance->NAME,
        ]);

        $aiConsumer = new AiApiConsumer([
            'base_path' => config('custom-services.apis.ai_api.base_path'),
            'api_key' => config('custom-services.apis.ai_api.api_key'),
        ]);

        $this->modifyDescriptionFromEntityAndReturn(
            $aiConsumer,
            $instance
            );

        if ($instance->TYPE =='simple') {
            $instanceCentral->product_self_commerce_id = $skuToFind;
        }

        \Log::info('item processado com sucesso SKU: '.$instance->sku);

        return $instance->save();
    }


    private function modifyDescriptionFromEntityAndReturn($aiConsumer, $entity)
    {

        $jsonElement = json_encode([
            'name' => $entity->title,
            'url' => $entity->url,
        ]);

        $promptTxt = sprintf(config('custom-services.apis.ai_api.prompts.search_product_on_glbal_find_portal'),$jsonElement);
        $systemConfigInstructions = sprintf(config('custom-services.apis.ai_api.system_config_instructions.web_search_techinical_infos_official_product'), $entity->NAME, $entity->URL);

        $aiResponse = $aiConsumer->sendContentToModelAi(
            $this->generatePayloadRequest(
                        $promptTxt,
                        $systemConfigInstructions
                    )
        );

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') request para IA, params: ', $jsonElement);
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') respoosta obtida de IA', $aiResponse);

        $responseApiFilled = $this->fillJustJsonMessageFromResponse(
                    $aiResponse['candidates'][0]['content']['parts'][0]['text']
                    )['array'];

        $entity->ean = $responseApiFilled['ean'];
        $entity->weight = $responseApiFilled['weight'];
        $entity->height = $responseApiFilled['height'];
        $entity->width = $responseApiFilled['width'];
        $entity->length = $responseApiFilled['length'];

        $entity->save();

        return $entity;
    }

    private function generatePayloadRequest($contentsPart, $configSystemInstructions)
    {
        return [
            "tools" => [
                ["googleSearch" => new \stdClass()]
            ],
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $contentsPart]
                    ]
                ]
            ],
            "systemInstruction" => [
                "parts" => [
                    ["text" => $configSystemInstructions]
                ]
            ],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "responseSchema" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "ean" => [
                            "type" => "STRING",
                            "description" => "Código EAN/GTIN do produto."
                        ],
                        "name" => [
                            "type" => "STRING",
                            "description" => "Nome do produto."
                        ],
                        "weight" => [
                            "type" => "NUMBER",
                            "description" => "Peso do produto com a unidade (ex: '1 kg')."
                        ],
                        "height" => [
                            "type" => "NUMBER",
                            "description" => "Altura da embalagem com a unidade (ex: '33 cm')."
                        ],
                        "width" => [
                            "type" => "NUMBER",
                            "description" => "Largura da embalagem com a unidade (ex: '20 cm')."
                        ],
                        "length" => [
                            "type" => "NUMBER",
                            "description" => "Comprimento/Profundidade da embalagem com a unidade (ex: '10 cm')."
                        ]
                    ],
                    "required" => [
                        "ean",
                        "name",
                        "weight",
                        "height",
                        "width",
                        "length"
                    ]
                ]
            ]
        ];
    }

    private function fillJustJsonMessageFromResponse($text)
    {
        if (preg_match('/```json\s*\n(.+?)```/s', $text, $matches)) {

            $jsonString = trim($matches[1]);

            $data = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE) {
               return [
                    'json' => $jsonString,
                    'array' => $data,
                ];
            }

            throw new \Exception("Erro ao decodificar JSON: " . json_last_error_msg());
        }

        throw new \Exception("Bloco JSON não encontrado no texto: ".$text);
    }


    public function configureForHeavyOperations()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        ini_set('memory_limit', '-1');

        ini_set('max_input_time', -1);
        ini_set('max_input_vars', 100000);
        ini_set('max_execution_time', 9800);

        ini_set('output_buffering', 'Off');
        ini_set('zlib.output_compression', 'Off');

        ini_set('pcre.backtrack_limit', 100000000);
        ini_set('pcre.recursion_limit', 100000000);

        ini_set('session.gc_maxlifetime', 86400);

        config(['app.debug' => true]);

    }

}
