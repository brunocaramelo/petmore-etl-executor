<?php

namespace App\Actions;

use App\Models\{ProductCentral,
                ProductRewrited
                };

use App\Consumers\AiApiConsumer;
use Illuminate\Support\Facades\{Storage,
                                Http
                            };
use Exception;

use Intervention\Image\ImageManager;

class CreateRewritedProductAction
{
    public function execute(ProductCentral $instance)
    {
        $this->configureForHeavyOperations();

        $instanceToNew = $instance->productMl()->first();
        $instanceToNewArr = $instanceToNew->toArray();

        $instanceToNewArr['sku'] = $instance->sku;

        if(empty($instanceToNewArr['title'])) {
            $instanceToNewArr['title'] = $instance->ploutos_descricao;
        }

        if(empty($instanceToNewArr['price'])) {
            $instanceToNewArr['price'] = [
                'current' => 11,
                'currency' => 'R$',
            ];
        }

        if(isset($instanceToNewArr['_id'])) unset($instanceToNewArr['_id']);
        if(isset($instanceToNewArr['id'])) unset($instanceToNewArr['id']);

        $toRewrite = ProductRewrited::create($instanceToNewArr);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') starting proccess to', [
            'sku' => $instanceToNewArr['sku'],
            'title' => $instanceToNewArr['title'],
            'count_variations' => count($instanceToNewArr['variations'] ?? []),
        ]);

        $aiConsumer = new AiApiConsumer([
            'base_path' => config('custom-services.apis.ai_api.base_path'),
            'api_key' => config('custom-services.apis.ai_api.api_key'),
        ]);

        $this->modifyDescriptionFromEntityAndReturn(
            $aiConsumer,
            ProductRewrited::find($toRewrite->uuid)
            );

        $instance->product_rewrited_id = $toRewrite->uuid;
        $instance->ai_adapted_the_content = true;

        \Log::info('item processado com sucesso SKU: '.$instanceToNewArr['sku']);

        return $instance->save();
    }


    private function modifyDescriptionFromEntityAndReturn($aiConsumer, $entity)
    {

        $jsonElement = json_encode([
            'complement' => $entity->description['complement']['html'],
            'small' => $entity->description['small']['html'],
        ]);

        $promptTxt = config('custom-services.apis.ai_api.prompts.modify_product_to_not_copyright').': '.$jsonElement;

        $aiResponse = $aiConsumer->sendContentToModelAi([
            'contents' => [
                'parts' => [
                    'text' => $promptTxt
                ]
            ]
        ]);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') respoosta obtida de IA', $aiResponse);

        $responseApiFilled = $this->fillJustJsonMessageFromResponse(
                    $aiResponse['candidates'][0]['content']['parts'][0]['text']
                    )['array'];

        $entity->description = [
                                'complement' => [
                                    'html' => $responseApiFilled['complement'],
                                    'text' => strip_tags($responseApiFilled['complement']),
                                ],
                                'small' => [
                                    'html' => $responseApiFilled['small'],
                                    'text' => strip_tags($responseApiFilled['small']),
                                ],
                            ];

        $entity->specifications = $this->prepareAndParseEspecifications($entity->specifications);

        $entity->images = $this->reparseImagesToLocalAndReplaceEntity($entity->images, $entity->sku);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') description, specifications and images setteds, now variations');

        $entity->variations = $this->reparseVariationsItems($entity->variations, $entity);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished variations');


        $entity->save();

        return $entity;
    }

    private function prepareAndParseEspecifications($originalList) : array
    {
        $returnArray = [];
        $resultAux = [];

        foreach ($originalList as $block) {
            if (!isset($block['rows']) || !is_array($block['rows'])) {
                continue;
            }

            foreach ($block['rows'] as $row) {
                $resultAux[] = $row;
            }
        }

        $returnArray = $resultAux;
        shuffle($returnArray);

        return $returnArray;
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

    private function downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($urlRemote, $skuSelf, $subDir)
    {
        $localFile = $this->downloadAndTransformMlImagesToLocalAndReturnPath($urlRemote, $skuSelf, $subDir);

        $pathSavedFile = str_replace(['.webp', 'images-products-to-erp'],
                                    ['.jpg', 'products-catalog'] ,
                            'images-products-to-erp/'.$skuSelf.'/'.$subDir.'/'.basename($urlRemote)
        );

        Storage::disk('choiced_cloud_storage')->put(
                                                        $pathSavedFile,
                                                    Storage::disk('local')->get(
                                                            str_replace(Storage::disk('local')->path('') ,
                                                                                            '' ,
                                                                                        $localFile)
                                                        )
                                                    );

        return Storage::disk('choiced_cloud_storage')->url($pathSavedFile);
    }

    private function downloadAndTransformMlImagesToLocalAndReturnPath($urlRemote, $skuSelf, $subDir)
    {
        if (filter_var($urlRemote, FILTER_VALIDATE_URL) == false) {
            return null;
        }

        $manager = new ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        $caminhoOriginalLocalDirStorage = 'tmp/original/images-products-to-erp/'.$skuSelf.'/'.$subDir;
        $caminhoOriginalLocalStorage = $caminhoOriginalLocalDirStorage.'/'.basename( $urlRemote);

        $caminhoOutStorageDir = Storage::disk('local')
                            ->path('images-products-to-erp/'.$skuSelf.'/'.$subDir);

        $caminhoOutStorage = $caminhoOutStorageDir.'/'.basename(
                                str_replace(['.webp'],['.jpg'],$urlRemote)
                                );

        try {

            $response = Http::get($urlRemote);

            if ($response->failed()) {
                return null;
            }

            Storage::disk('local')
                ->put($caminhoOriginalLocalStorage, $response->body());

            $pathCompletoOriginal = Storage::disk('local')->path($caminhoOriginalLocalStorage);

            if (!is_dir($caminhoOutStorageDir)) {
                mkdir($caminhoOutStorageDir, 0755, true);
            }

            $image = $manager->read($pathCompletoOriginal);

            $originalWidth = $image->width();
            $originalHeight = $image->height();

            $minusSizeResizePixelValue = rand(1, 2);

            $newWidth = max(0, $originalWidth - $minusSizeResizePixelValue);
            $newHeight = max(0, $originalHeight - $minusSizeResizePixelValue);

            $image->resize($newWidth, $newHeight);

            $encoded = $image->toJpeg(97);

            $encoded->save($caminhoOutStorage);

            Storage::disk(name: 'local')->delete($caminhoOriginalLocalStorage);

            return $caminhoOutStorage;

        } catch (\Exception $e) {
            \Log::error('Falha ao processar a imagem do Mercado Livre: ' . $e->getMessage());
            return null;
        }
    }

    private function reparseImagesToLocalAndReplaceEntity($images, $sku)
    {
        $shuffledItems = $images;
        shuffle($shuffledItems);

        $listLocalImages = [];

        $limitToRemoveItem = 3;
        $needRemoveItem = (count($shuffledItems) > $limitToRemoveItem);

        foreach ($shuffledItems as $indexImage => $valueImage) {
            if ($needRemoveItem && 0 == $indexImage) {
                \Log::debug("mais de $limitToRemoveItem, removendo imagem",[
                    'index' => $indexImage,
                    'thumbnail' => $valueImage['thumbnail'],
                ]);
                continue;
            }

            if(!empty($valueImage['thumbnail'])) $listLocalImages[$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['thumbnail'], $sku, 'base');
            if(!empty($valueImage['mid_size'])) $listLocalImages[$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['mid_size'], $sku, 'base');
            if(!empty($valueImage['full_size'])) $listLocalImages[$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['full_size'], $sku, 'base');
        }

        return $listLocalImages;
    }

    private function reparseVariationsItems($listVariationsOriginal, $parent) : array
    {
        $returnVariations = [];

        $shuffledVariationsItems = $listVariationsOriginal;
        shuffle($shuffledVariationsItems);

        \Log::debug(__CLASS__.' ('.__FUNCTION__.') start integrations');

        foreach ($shuffledVariationsItems as $indexVariation => $valueVariation) {

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') start item process' ,[
                'title' => $valueVariation['title'],
                'price' => $valueVariation['price']['current'],
            ]);

            $valuesAttributes = collect($valueVariation['attributes'])->map(function ($item) {
                return \Str::slug(trim(empty($item[1]['value']) ? 'no_category' : $item[1]['value']));
            });

            $sluggedValues = $valuesAttributes->implode('-');

            $returnVariations[$indexVariation] = $valueVariation;

            $returnVariations[$indexVariation]['attributes'] = $valueVariation['attributes'];
            $returnVariations[$indexVariation]['description'] = $parent->description;
            $returnVariations[$indexVariation]['specifications'] = $this->prepareAndParseEspecifications($valueVariation['specifications'] ?? []);
            $returnVariations[$indexVariation]['title'] = $valueVariation['title'];
            $returnVariations[$indexVariation]['price'] = $valueVariation['price'];
            $returnVariations[$indexVariation]['images'] = $this->reparseVariationsImagesToLocalAndReplaceEntity($valueVariation['images'] ?? [], $sluggedValues, $parent->sku);
            $returnVariations[$indexVariation]['available'] = $valueVariation['available'] ?? null;
            $returnVariations[$indexVariation]['url'] = $valueVariation['url'] ?? null;

            \Log::debug(__CLASS__.' ('.__FUNCTION__.') finished item process to title: '.$valueVariation['title']);
        }


        return $returnVariations;
    }

    private function reparseVariationsImagesToLocalAndReplaceEntity($listVariationsOriginal, $sluggedValues, $sku) : array
    {
        $listVariations = $listVariationsOriginal;

        $shuffledVariations = $listVariations;
        shuffle($shuffledVariations);

        $limitToRemoveItem = 3;
        $needRemoveItem = (count($shuffledVariations) > $limitToRemoveItem);

        foreach ($shuffledVariations as $indexImage => $valueImage) {
            if ($needRemoveItem && 0 == $indexImage) {
                \Log::debug("mais de $limitToRemoveItem, removendo imagem",[
                    'index' => $indexImage,
                    'thumbnail' => $valueImage['thumbnail'],
                ]);
                continue;
            }

            if(!empty($valueImage['thumbnail'])) $shuffledVariations[$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['thumbnail'], $sku, $sluggedValues);
            if(!empty($valueImage['mid_size'])) $shuffledVariations[$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['mid_size'], $sku, $sluggedValues);
            if(!empty($valueImage['full_size'])) $shuffledVariations[$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['full_size'], $sku, $sluggedValues);
        }

        return $shuffledVariations;
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
