<?php

return [
    'apis' => [
        'bling_erp' => [
            'base_path' => env('BLING_ERP_BASE_PATH'),
            'client_id' => env('BLING_ERP_CLIENT_ID'),
            'client_secret' => env('BLING_ERP_CLIENT_SECRET'),
            'redirect_uri' => env('BLING_ERP_REDIRECT_URI'),
            'access_token' => env('BLING_ERP_ACCESS_TOKEN'),
            'refresh_token' => env('BLING_ERP_REFRESH_TOKEN'),
            'settings' => [
                'custom_fields' => [
                    'types' => [
                        'string' => env('BLING_ERP_SETTINGS_CUSTOM_STRING_FIELD_ID', 4),
                        'long_string' => env('BLING_ERP_SETTINGS_CUSTOM_LONG_STRING_FIELD_ID', 4)
                    ],
                    'groupers' => [
                        'default' => env('BLING_ERP_SETTINGS_CUSTOM_FIELD_GROUPER_ID', 11806652),
                    ],
                    'modules' => [
                        'default' => env('BLING_ERP_SETTINGS_CUSTOM_FIELD_MODULE_ID', 98309),
                    ],
                ]
            ]
        ],
        'self_ecommerce' => [
            'base_url' => env('ECOMMERCE_BASE_URL'),
            'admin_username' => env('ECOMMERCE_CONSUMER_USER', 'none'),
            'admin_password' => env('ECOMMERCE_CONSUMER_PASSWORD', 'none'),
            'store_default_code' => env('ECOMMERCE_TENANT_CODE'),
        ],
        'mercado_livre_scrapper' => [
            'callback_url' => env('MERCADO_LIVRE_WEBHOOK_TARGET'),
            'base_path' => env('MERCADO_LIVRE_SCRAPPER_BASE_PATH'),
        ],
        'ai_api' => [
            'base_path' => env('AI_API_BASE_PATH'),
            'api_key' => env('AI_API_KEY'),
            'prompts' => [
                          'modify_product_to_not_copyright' => env('AI_API_PROMPT_MODIFY_PRODUCT_TO_NOT_COPYRIGHT')
                        ],
        ]
    ]

];
