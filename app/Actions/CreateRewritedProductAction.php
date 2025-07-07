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
        $instanceToNew = $instance->productMl()->first();
        $instanceToNewArr = $instanceToNew->toArray();

        if(isset($instanceToNewArr['_id'])) unset($instanceToNewArr['_id']);
        if(isset($instanceToNewArr['id'])) unset($instanceToNewArr['id']);

        $toRewrite = ProductRewrited::create($instanceToNewArr);

        $aiConsumer = new AiApiConsumer([
            'base_path' => config('custom-services.apis.ai_api.base_path'),
            'api_key' => config('custom-services.apis.ai_api.api_key'),
        ]);

        $toRewrite = $this->modifyDescriptionFromEntityAndReturn($aiConsumer,$instanceToNew);

        $instance->product_rewrited_id = $toRewrite->uuid;
        $instance->ai_adapted_the_content = true;

        return $instance->save();
    }


    private function modifyDescriptionFromEntityAndReturn($aiConsumer, $entity)
    {
        $jsonElement = json_encode([
            'description' => $entity->description['html'],
            'specifications' => $entity->specifications,
        ]);

        $promptTxt = config('custom-services.apis.ai_api.prompts.modify_product_to_not_copyright').': '.$jsonElement;

        // $aiResponse = $aiConsumer->sendContentToModelAi([
        //     'contents' => [
        //         'parts' => [
        //             'text' => $promptTxt
        //         ]
        //     ]
        // ]);

        // $responseApiFilled = $this->fillJustJsonMessageFromResponse(
        //             $aiResponse['candidates'][0]['content']['parts'][0]['text']
        //             )['array'];

        // $entity->description = [
        //                        'html' => $responseApiFilled['description'],
        //                        'text' => strip_tags($responseApiFilled['description']),
        //                     ];

        // $entity->specifications = $responseApiFilled['specifications'];

        $entity->images = $this->reparseImagesToLocalAndReplaceEntity($entity->images, $entity->sku);

        $entity->variations = $this->reparseVariationsImagesToLocalAndReplaceEntity($entity->variations, $entity->sku);


        $entity->save();

        \Log::info($entity->toArray());

        return $entity;
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

    private function downloadAndTransformMlImagesToLocalAndReturnPath($urlRemote, $skuSelf, $subDir)
    {
        $manager = new ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        $caminhoOriginalLocalDirStorage = 'tmp/original/images-products-to-erp/'.$skuSelf.$subDir;
        $caminhoOriginalLocalStorage = $caminhoOriginalLocalDirStorage.'/'.basename( $urlRemote);

        $caminhoOutStorageDir = Storage::disk('local')
                            ->path('images-products-to-erp/'.$skuSelf.$subDir);

        $caminhoOutStorage = $caminhoOutStorageDir.'/'.basename(
                                str_replace(['.webp'],['.jpge'],$urlRemote)
                                );

        $response = Http::get($urlRemote);

        Storage::disk('local')
                ->put($caminhoOriginalLocalStorage, $response->body());


        $pathCompletoOriginal = Storage::disk('local')->path($caminhoOriginalLocalStorage);

        if (!Storage::disk('local')->exists($caminhoOutStorageDir)) {
            mkdir($caminhoOutStorageDir, 0755, true);
        }

        $image = $manager->read($pathCompletoOriginal);

        $encoded = $image->toJpeg(97);

        $encoded->save($caminhoOutStorage);

        Storage::disk(name: 'local')->delete($caminhoOriginalLocalStorage);

        return $caminhoOutStorage;
    }

    private function reparseImagesToLocalAndReplaceEntity($images, $sku)
    {
        $listImages = $images;

        $listLocalImages = [];

        foreach ($listImages as $indexImage => $valueImage) {
            if(!empty($valueImage['thumbnail'])) $listLocalImages[$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['thumbnail'], $sku, 'base');
            if(!empty($valueImage['mid_size'])) $listLocalImages[$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['mid_size'], $sku, 'base');
            if(!empty($valueImage['full_size'])) $listLocalImages[$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['full_size'], $sku, 'base');
        }

        return $listLocalImages;
    }
    private function reparseVariationsImagesToLocalAndReplaceEntity($listImages, $sku)
    {
        $listLocalImages = [];

        foreach ($listImages as $indexImage => $valueImage) {
            $valuesAttributes = collect($valueImage['attributes'])->pluck('value');
            $sluggedValues = $valuesAttributes->map(function ($value) {
                $cleanValue = trim(str_replace(["\n", "\r"], '', $value));
                return \Str::slug($cleanValue);
            });

            if(!empty($valueImage['images'][$indexImage]['thumbnail'])) $listLocalImages['images'][$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['images'][$indexImage]['thumbnail'], $sku, $sluggedValues);
            if(!empty($valueImage['images'][$indexImage]['mid_size'])) $listLocalImages['images'][$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['images'][$indexImage]['mid_size'], $sku, $sluggedValues);
            if(!empty($valueImage['images'][$indexImage]['full_size'])) $listLocalImages['images'][$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToLocalAndReturnPath($valueImage['images'][$indexImage]['full_size'], $sku, $sluggedValues);
        }

        return $listLocalImages;
    }

}
