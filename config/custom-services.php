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
            'domain_url' => env('ECOMMERCE_DOMAIN'),
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
                          'modify_product_to_not_copyright' => env('AI_API_PROMPT_MODIFY_PRODUCT_TO_NOT_COPYRIGHT'),
                          'search_product_on_glbal_find_portal' => env('AI_API_PROMPT_SEARCH_PRODUCT_ON_GLBAL_FIND_PORTAL'),
                        ],
            'system_config_instructions' => [
                'web_search_techinical_infos_official_product' => env('AI_API_WEB_SEARCH_TECHINICAL_INFOS_OFFICIAL_PRODUCT'),
            ]
        ]
    ],
    'jobs_intervals' => [
        'minutes' => [
            'mercado-livre-scrap' => [
                'queue' => env('QUEUE_NAME_MERCADO_LIVRE_SCRAP', 'queue_mercado_livre_scrap'),
                'min' => env('INTERVAL_JOB_ML_SCRAP_MIN', 5),
                'max' => env('INTERVAL_JOB_ML_SCRAP_MAX', 13),
            ],
            'modify-copyright' => [
                'queue' => env('QUEUE_NAME_MODIFY_COPYRIGHT', 'queue_modify_copyright'),
                'min' => env('INTERVAL_JOB_ML_COPYRIGHT_MIN', 5),
                'max' => env('INTERVAL_JOB_ML_COPYRIGHT_MAX', 13),
            ],
            'send-self-ecommerce' => [
                'queue' => env('QUEUE_NAME_SEND_SELF_ECOMMERCE', 'queue_send_self_ecommerce'),
                'min' => env('INTERVAL_JOB_ML_SEND_SELF_ECOMMERCE_MIN', 5),
                'max' => env('INTERVAL_JOB_ML_SEND_SELF_ECOMMERCE_MAX', 13),
            ],
            'send-self-ecommerce-maintance' => [
                'queue' => env('QUEUE_NAME_SEND_MAINTAIN_SELF_ECOMMERCE', 'queue_send_self_ecommerce_maintance'),
                'min' => env('INTERVAL_JOB_ML_SEND_SELF_ECOMMERCE_MAINTANCE_MIN', 10),
                'max' => env('INTERVAL_JOB_ML_SEND_SELF_ECOMMERCE_MAINTANCE_MAX', 21),
            ],
            'send-ean-and-shipping-data-self-ecommerce' => [
                'queue' => env('QUEUE_NAME_SEND_MAINTAIN_SHIPPING_DATA_SELF_ECOMMERCE', 'queue_send_self_ecommerce_shipping_data_maintance'),
                'min' => env('INTERVAL_JOB_ML_SEND_UPDATE_SHIPPING_DATA_MIN', 5),
                'max' => env('INTERVAL_JOB_ML_SEND_UPDATE_SHIPPING_DATA_MAX', 13),
            ],
            'send-erp-platform' => [
                'queue' => env('QUEUE_NAME_SEND_ERP_PLATFORM', 'queue_send_erp_platform'),
                'min' => env('INTERVAL_JOB_ML_SEND_ERP_PLATFORM_MIN', 5),
                'max' => env('INTERVAL_JOB_ML_SEND_ERP_PLATFORM_MAX', 13),
            ],
        ]
    ]

];
