<?php

namespace App\Console\Commands;

use App\Models\ProductSelfCommerceData;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Consumers\SelfEcommerceAuthConsumer;
use App\Consumers\SelfEcommerceConsumer;

class SendMantainAdjustNeedleProductToSelfEcommerceTool extends Command
{

    protected $signature = 'maintain:prepare-changes-product-to-self-ecommerce-tool-to-hub-integration';
    protected $description = 'Envio de dados mapeados de produto ao Ecommerce proprio.';

    public function handle()
    {
        $pendingItems = ProductSelfCommerceData::where('has_searched', true)
                        // ->where('official_data_sended', false)
                        ->get();

        \Log::info("(SendMantainAdjustNeedleProductToSelfEcommerceTool) Itens pendentes encontrados para serem processados ".$pendingItems->count());


        $consumerInstance = new SelfEcommerceConsumer(
                new SelfEcommerceAuthConsumer(
                    config('custom-services.apis.self_ecommerce.admin_username'),
                    config('custom-services.apis.self_ecommerce.admin_password')
                ), [
            'base_path' => config('custom-services.apis.self_ecommerce.base_url'),
            'auto_login' => true,
        ]);


        foreach ($pendingItems as $pending) {

            $delayToJob = Carbon::now();

            sleep(rand(
                    config('custom-services.jobs_intervals.minutes.send-ean-and-shipping-data-self-ecommerce.min'),
                    config('custom-services.jobs_intervals.minutes.send-ean-and-shipping-data-self-ecommerce.max'),
                ));

            $this->sendOfficialAttrs($consumerInstance, $pending);

           \Log::info("(SendMantainAdjustNeedleProductToSelfEcommerceTool) Job para item ".($pending->sku ?? 'sku')." para envio ao ecommerce com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMantainAdjustNeedleProductToSelfEcommerceTool) Processo finalizado");

    }

    private function sendOfficialAttrs($consumer, $itemParam)
    {
        $consumer->updateProduct($itemParam->sku,
            $this->prepareParamsToSendUpdate(
                $itemParam
            )
        );

        $itemParam->official_data_sended = true;

        $itemParam->save();

    }

    private function prepareParamsToSendUpdate($instance)
    {
        $result = [
            'id' => $instance->entity_id,
        ];

        if (($instance->has_searched ?? false) == true) {
            if (is_numeric(trim($instance->ean))) {
                $result['custom_attributes'][] = [
                    'attribute_code' => 'ean',
                    'value' => trim($instance->ean)
                ];

                // $result['ean']= trim($instance->ean);
            }

            if (is_numeric(trim($instance->height))) {
                $result['custom_attributes'][] = [
                    'attribute_code' => 'volume_height',
                    'value' => trim($instance->height)
                ];
            }

            if (is_numeric(trim($instance->weight))) {
                $result['weight'] = trim($instance->weight);
            }

            if (is_numeric(trim($instance->length))) {
                $result['custom_attributes'][] = [
                    'attribute_code' => 'volume_length',
                    'value' => trim($instance->length)
                ];            }

            if (is_numeric(trim($instance->width))) {
                $result['custom_attributes'][] = [
                    'attribute_code' => 'volume_width',
                    'value' => trim($instance->width)
                ];
            }
        }

        $productPayload = ['product' => $result];

        \Log::info('payload to Send actual SKU: '.$instance->sku, $productPayload);

        return $productPayload;
    }

}
