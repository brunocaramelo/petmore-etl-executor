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
    public function execute(ProductSelfCommerceData $instance, array $config)
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

        if ($config['search_and_storage_stage']) {
            $this->searchAndStorageOnStage(
                $aiConsumer,
                $instance
                );
        }

        if ($instance->TYPE == 'simple') {
            $instanceCentral->product_self_commerce_id = $skuToFind;
            $instanceCentral->save();
        }

        \Log::info('item processado com sucesso SKU: '.$instance->sku);

        return $instance->save();
    }


    private function searchAndStorageOnStage($aiConsumer, $entity)
    {
        $promptTxt = sprintf(config('custom-services.apis.ai_api.prompts.search_product_on_glbal_find_portal'), $entity->NAME);
        $systemInstructionText = sprintf(config('custom-services.apis.ai_api.system_config_instructions.web_search_techinical_infos_official_product'), $entity->NAME);

        $promptArr = $this->generatePayloadRequest($promptTxt, $systemInstructionText);

        // $promptArr['contents'][0][0]['parts']['text'] = json_encode($promptArr, JSON_UNESCAPED_UNICODE);

        $aiResponse = $aiConsumer->sendContentToModelAiBodyRawArr($promptArr);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') request para IA, params: ', [$promptArr, $systemInstructionText]);
        \Log::debug(__CLASS__.' ('.__FUNCTION__.') respoosta obtida de IA', [$aiResponse]);

        $lineWithJsonObject = null;
        $contentHasFoundedOfficial = false;

        foreach ($aiResponse['candidates'][0]['content']['parts'] as $contentReturn) {
            if (stripos($contentReturn['text'], '``json') !== false) {
                $contentHasFoundedOfficial = true;
                $lineWithJsonObject = $contentReturn['text'];
                break;
            }
        }

        if (!$contentHasFoundedOfficial) {
            return $entity;
        }

        $responseApiFilled = $this->fillJustJsonMessageFromResponse(
            $lineWithJsonObject
        )['array'];

        $entity->ean = strtolower($responseApiFilled['ean']) !='unknown' ? $responseApiFilled['ean'] : null;
        $entity->weight = is_numeric($responseApiFilled['weight']) ? $responseApiFilled['weight'] : null;
        $entity->height = is_numeric($responseApiFilled['weight']) ? $responseApiFilled['height'] : null;
        $entity->width =  is_numeric($responseApiFilled['weight']) ? $responseApiFilled['width'] : null;
        $entity->length = is_numeric($responseApiFilled['weight']) ? $responseApiFilled['length'] : null;

        $entity->has_searched = true;

        $entity->save();

        return $entity;
    }

    private function generatePayloadRequest($contentsPart, $systemInstructionText)
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
            "systemInstruction"=> [
            "parts"=> [
                [
                    "text"=> $systemInstructionText
                ],
            ],
        ],
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
