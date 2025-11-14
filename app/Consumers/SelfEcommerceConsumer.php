<?php

namespace App\Consumers;

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
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/eav/attribute-sets', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function createAttibuteSetItem(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attributes', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }


    public function attachAttibuteIntoGroupAttrSet(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attribute-sets/attributes', $params)
                    ->throw();

        if ($response->successful()) {
            return str_replace('"','', $response->body());
        }

        return null;
    }

    public function attachOptionIntoAttibuteAttrSet($attributeId, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attributes/'.$attributeId.'/options', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function attachOptionAttibuteAttrIntoConfigurableProduct($productSku, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/configurable-products/'.$productSku.'/options', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function attachProductChildIntoConfigurableProduct($productSku, array $params)
    {
        \Log::info(__CLASS__.' ('.__FUNCTION__.') request', [
            'url' => $this->baseApiPath.'/configurable-products/'.$productSku.'/child',
            'body' => $params,
            'token' => $this->tokenAuth,
        ]);

        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/configurable-products/'.$productSku.'/child', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function attachOptionToAttributeItem($attrCode, array $option)
    {
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
                    ])->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
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
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products/attribute-sets/groups', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }


    public function createProduct(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/products', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function createMediaImagesIntoProductSku($productSku ,array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath."/products/{$productSku}/media", $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function createCustomField(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/campos-customizados', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateCustomField($id, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->put($this->baseApiPath.'/campos-customizados/'.$id, $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function getProduct($identify)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->get($this->baseApiPath.'/produtos/'.$identify);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateProduct($identify, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->put($this->baseApiPath.'/produtos/'.$identify);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function sendAuthApi()
    {
        $responseApi = $this->authInstance->byStoredToken();
        $this->tokenAuth = $responseApi;
    }

}
