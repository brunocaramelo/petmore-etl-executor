<?php

namespace App\Actions;

use App\Consumers\{BlingErpConsumer,
                  BlingOauthConsumer};

class OpenPlanPloutosToPersistIntoDatabaseAction
{
    public function execute(array $params)
    {
        $paramsTranslate = $params;

        $consumer = new BlingErpConsumer( new BlingOauthConsumer(), [
            'auto_login' => true,
            'base_path' => config('custom-services.apis.bling_erp.base_path'),
        ]);

        return $consumer->createProduct($paramsTranslate);
    }
}