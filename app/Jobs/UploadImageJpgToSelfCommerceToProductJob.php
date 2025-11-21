<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Storage;

class UploadImageJpgToSelfCommerceToProductJob implements ShouldQueue
{
    use Queueable;

    private $productSku;
    private $pathOfImage;
    private $consumer;

    public function __construct(
        string $productSku,
        string $pathOfImage,
        $consumer,
    ) {
        $this->productSku = $productSku;
        $this->pathOfImage = $pathOfImage;
        $this->consumer = $consumer;
    }


    public function handle(): void
    {
        $clearHttpPathStorage = 'https://br-se1.magaluobjects.com/petmore-public/';

        $clearPath = str_replace([$clearHttpPathStorage],[''], $this->pathOfImage);

        $fileContentTarget = Storage::disk('choiced_cloud_storage')->get($clearPath);

        $baseFileNameToSend = str_replace(['.jpge', '.jpeg'],['.jpg','.jpg'], basename($clearPath));

        $payload = [
            "entry" => [
                "media_type" => "image",
                "label" => $img['label'] ?? '',
                "position" => $img['position'] ?? 1,
                "disabled" => false,
                "types" => $img['types'] ?? ['image', 'small_image', 'thumbnail'],
                "content" => [
                    "name" => $baseFileNameToSend,
                    "type" => "image/jpeg",
                    "base64_encoded_data" => base64_encode($fileContentTarget),
                ]
            ]
        ];

        \Log::info(__CLASS__.' ('.__FUNCTION__.') before send createMediaImagesIntoProductSku');

        $this->consumer->createMediaImagesIntoProductSku($this->productSku, $payload);

        \Log::info(__CLASS__.' ('.__FUNCTION__.') after send createMediaImagesIntoProductSku');


    }
}
