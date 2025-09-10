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

        $instanceToNewArr['sku'] = $instance->sku;

        if(isset($instanceToNewArr['_id'])) unset($instanceToNewArr['_id']);
        if(isset($instanceToNewArr['id'])) unset($instanceToNewArr['id']);

        $toRewrite = ProductRewrited::create($instanceToNewArr);

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

        return $instance->save();
    }


    private function modifyDescriptionFromEntityAndReturn($aiConsumer, $entity)
    {
        $jsonElement = json_encode([
            'complement' => $entity->description['complement']['html'],
            'small' => $entity->description['small']['html'],
            'specifications' => $entity->specifications,
        ]);

        $promptTxt = config('custom-services.apis.ai_api.prompts.modify_product_to_not_copyright').': '.$jsonElement;

        $aiResponse = $aiConsumer->sendContentToModelAi([
            'contents' => [
                'parts' => [
                    'text' => $promptTxt
                ]
            ]
        ]);

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

        $entity->specifications = $responseApiFilled['specifications'];

        $entity->images = $this->reparseImagesToLocalAndReplaceEntity($entity->images, $entity->sku);

        $entity->variations = $this->reparseVariationsImagesToLocalAndReplaceEntity($entity->variations, $entity->sku);

        $entity->save();

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

    private function downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($urlRemote, $skuSelf, $subDir)
    {
        $localFile = $this->downloadAndTransformMlImagesToLocalAndReturnPath($urlRemote, $skuSelf, $subDir);

        $pathSavedFile = str_replace(['.webp', 'images-products-to-erp'],
                                    ['.jpge', 'products-catalog'] ,
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
                                str_replace(['.webp'],['.jpge'],$urlRemote)
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
        $listImages = $images;

        $listLocalImages = [];

        foreach ($listImages as $indexImage => $valueImage) {
            if(!empty($valueImage['thumbnail'])) $listLocalImages[$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['thumbnail'], $sku, 'base');
            if(!empty($valueImage['mid_size'])) $listLocalImages[$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['mid_size'], $sku, 'base');
            if(!empty($valueImage['full_size'])) $listLocalImages[$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['full_size'], $sku, 'base');
        }

        return $listLocalImages;
    }

    private function reparseVariationsImagesToLocalAndReplaceEntity($listVariationsOriginal, $sku)
    {
        $listVariations = $listVariationsOriginal;

        foreach ($listVariations as $indexVariations => $valueVariations) {

            $valuesAttributes = collect($valueVariations['attributes'])->map(function ($item) {
                return \Str::slug(trim(empty($item[1]['value']) ? 'no_category' : $item[1]['value']));
            });

            $sluggedValues = $valuesAttributes->implode('-');

            foreach ($valueVariations['images'] as $indexImage => $valueImage) {
                if(!empty($valueImage['thumbnail'])) $listVariations[$indexVariations]['images'][$indexImage]['thumbnail'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['thumbnail'], $sku, $sluggedValues);
                if(!empty($valueImage['mid_size'])) $listVariations[$indexVariations]['images'][$indexImage]['mid_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['mid_size'], $sku, $sluggedValues);
                if(!empty($valueImage['full_size'])) $listVariations[$indexVariations]['images'][$indexImage]['full_size'] = $this->downloadAndTransformMlImagesToRemoteStorageAndReturnPathAnd($valueImage['full_size'], $sku, $sluggedValues);
            }
        }

        return $listVariations;
    }

}
