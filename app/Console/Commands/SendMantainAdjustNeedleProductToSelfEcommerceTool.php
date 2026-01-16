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

            sleep(rand(5, 9));

            $this->executeRulesSelfProduct($consumerInstance, $pending);

           \Log::info("(SendMantainAdjustNeedleProductToSelfEcommerceTool) Job para item ".($pending->sku ?? 'sku')." para envio ao ecommerce com atraso para: " . $delayToJob);

        }
        \Log::info("(SendMantainAdjustNeedleProductToSelfEcommerceTool) Processo finalizado");

    }


    private function executeRulesSelfProduct($consumer, $pending)
    {
        return $this->regenerateSkuChildAndSendOfficialAttrs($consumer, $pending);
    }

    private function regenerateSkuChildAndSendOfficialAttrs($consumer, $itemParam)
    {
        $currentSkuChild = 'PM'.$this->randomUpperAlnum(10);
        // $currentSkuChild = $itemParam->sku;

        $consumer->updateProduct($itemParam->sku,
            $this->prepareParamsToSendUpdate(
                $currentSkuChild,
                $itemParam
            )
        );

        $itemParam->sku = $currentSkuChild;
        $itemParam->official_data_sended = true;

        $itemParam->save();

    }

    private function randomUpperAlnum(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    private function prepareParamsToSendUpdate($sku, $instance)
    {
        $result = [
            'id' => $instance->entity_id,
            'sku' => $sku
        ];

        // $result = [
        // ];

        if (($instance->has_searched ?? false) == true) {
            if (is_numeric(trim($instance->ean))) {
                $result['custom_attributes'][] = [
                    'attribute_code' => 'ean',
                    'value' => trim($instance->ean)
                ];
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
