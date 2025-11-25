<?php

namespace App\Consumers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SelfEcommerceConsumer
{
    private $baseApiPath;
    private $tokenAuth;
    private $config;
    private $authInstance;

    public function __construct($authInstance, array $config)
    {
        $this->baseApiPath = $config['base_path'];
        $this->config = $config;
        $this->authInstance = $authInstance;

        if ($config['auto_login'] ?? false) {
            $this->sendAuthApi();
        }

    }

    public function createAttibuteSet(array $params)
    {
        try {
            $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/eav/attribute-sets', $params);

            if ($response->failed()) {

                    \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                        'json'    => $response->json(),
                    ]);

                    throw new RequestException($response);
            }

            return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function createAttibuteSetItem(array $params)
    {
        try {
            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->post($this->baseApiPath.'/products/attributes', $params)
                        ;

            if ($response->failed()) {

                    \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                        'json'    => $response->json(),
                    ]);

                    throw new RequestException($response);
            }

            return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }


    public function attachAttibuteIntoGroupAttrSet(array $params)
    {
        try {
            $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attribute-sets/attributes', $params)
                    ;

            if ($response->failed()) {

                            \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                                'status'  => $response->status(),
                                'body'    => $response->body(),
                                'json'    => $response->json(),
                            ]);

                            throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function attachOptionIntoAttibuteAttrSet($attributeId, array $params)
    {

        try{
            $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attributes/'.$attributeId.'/options', $params)
                    ;

            if ($response->failed()) {

                            \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                                'status'  => $response->status(),
                                'body'    => $response->body(),
                                'json'    => $response->json(),
                            ]);

                            throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function attachOptionAttibuteAttrIntoConfigurableProduct($productSku, array $params)
    {
        try {
            $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/configurable-products/'.$productSku.'/options', $params)
                    ;

            if ($response->failed()) {

                            \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                                'status'  => $response->status(),
                                'body'    => $response->body(),
                                'json'    => $response->json(),
                            ]);

                            throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function attachProductChildIntoConfigurableProduct($productSku, array $params)
    {
        try {

            \Log::info(__CLASS__.' ('.__FUNCTION__.') (API TO SEND):', [
                        'productSku'  => $productSku,
                        'params'    => $params,
                    ]);

            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->post($this->baseApiPath.'/configurable-products/'.$productSku.'/child', $params);

            if ($response->failed()) {

                    \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                        'json'    => $response->json(),
                    ]);

                    throw new \Exception($response->body());
                }

                return $response->json();

        } catch (\Throwable $e) {

            \Log::error(__CLASS__.'::'.__FUNCTION__.' (EXCEPTION CAUGHT)', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e;
        }

    }

    public function attachOptionToAttributeItem($attrCode, array $option)
    {
        try {
            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->post($this->baseApiPath.'/products/attributes/'.$attrCode.'/options', [
                            'option' => [
                                'label' => $option['label'],
                                'value' => (string) $option['value'],
                                'sort_order' => $option['label'],
                                'is_default' => false,
                            ]
                        ]);

            if ($response->failed()) {

                            \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                                'status'  => $response->status(),
                                'body'    => $response->body(),
                                'json'    => $response->json(),
                            ]);

                            throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function getGroupsFromAttributeSet(int $attributeSetId, ?string $groupName = null)
    {
        $url = $this->baseApiPath . "/products/attribute-sets/groups/list";
        $query = [
            'searchCriteria[filterGroups][0][filters][0][field]' => 'attribute_set_id',
            'searchCriteria[filterGroups][0][filters][0][value]' => $attributeSetId,
            'searchCriteria[filterGroups][0][filters][0][condition_type]' => 'eq',
        ];

        if ($groupName) {
            $query['searchCriteria[filterGroups][1][filters][0][field]'] = 'attribute_group_name';
            $query['searchCriteria[filterGroups][1][filters][0][value]'] = "%{$groupName}%";
            $query['searchCriteria[filterGroups][1][filters][0][condition_type]'] = 'like';
        }

        $response = Http::retry(3, 10)
            ->withToken($this->tokenAuth)
            ->timeout(30)
            ->get($url, $query)
            ->throw();

        if ($response->successful()) {
            return $response->json()['items'] ?? [];
        }

        return [];
    }

    public function addGroupAttibuteIntoAttributeSet(array $params)
    {
        try {
            $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attribute-sets/groups', $params)
                    ;

            if ($response->failed()) {

                        \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                            'status'  => $response->status(),
                            'body'    => $response->body(),
                            'json'    => $response->json(),
                        ]);

                        throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }


    public function createProduct(array $params)
    {
        try {

            $response = Http::retry(3, 10)
                ->withToken($this->tokenAuth)
                ->timeout(8999)
                ->post($this->baseApiPath.'/products', $params);

            if ($response->failed()) {

                \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'json'    => $response->json(),
                    'params'  => $params
                ]);

                throw new RequestException($response);
            }

            return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function createMediaImagesIntoProductSku($productSku ,array $params)
    {
        try {
            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->post($this->baseApiPath."/products/{$productSku}/media", $params)
                        ;

            if ($response->failed()) {

                    \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                        'json'    => $response->json(),
                        'params'  => $params
                    ]);

                    throw new RequestException($response);
                }

            return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }


    public function getProduct($identify)
    {
        try {
            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->get($this->baseApiPath.'/products/'.$identify);

            if ($response->failed()) {

                    \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                        'json'    => $response->json(),
                    ]);

                    throw new RequestException($response);
                }

            return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function updateProduct($identify, array $params)
    {
        try{
            $response = Http::retry(3, 10)
                        ->withToken($this->tokenAuth)
                        ->timeout(8999)
                        ->put($this->baseApiPath.'/products/'.$identify, $params);

            if ($response->failed()) {

                        \Log::error(__CLASS__.' ('.__FUNCTION__.') (API RETURN):', [
                            'status'  => $response->status(),
                            'body'    => $response->body(),
                            'json'    => $response->json(),
                        ]);

                        throw new RequestException($response);
                    }

                return $response->json();

        } catch (\Exception $e) {

            throw new \Exception(
                __CLASS__.' ('.__FUNCTION__.') (EXCEPTION RETURN):' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function sendAuthApi()
    {
        $responseApi = $this->authInstance->byStoredToken();
        $this->tokenAuth = $responseApi;
    }

}
