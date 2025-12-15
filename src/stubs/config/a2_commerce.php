<?php

return [
    /*
    |--------------------------------------------------------------------------
    | A2 Commerce Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for A2 Commerce module including payment gateways,
    | tax rates, shipping fees, and order settings.
    |
    */

    // Order Settings
    'order_prefix' => env('A2_ORDER_PREFIX', 'ORD'),

    // Tax Settings
    'tax_rate' => env('A2_TAX_RATE', 0.20), // 20% default

    // Shipping Settings
    'shipping_fee' => env('A2_SHIPPING_FEE', 0), // Free shipping by default

    // PayPal Configuration
    'paypal' => [
        'client_id' => env('A2_PAYPAL_CLIENT_ID'),
        'secret' => env('A2_PAYPAL_SECRET'),
        'mode' => env('A2_PAYPAL_MODE', 'sandbox'), // 'sandbox' or 'live'
        'webhook_id' => env('A2_PAYPAL_WEBHOOK_ID'),
    ],

    // Currency Settings
    'currency' => env('A2_CURRENCY', 'KES'),
    'currency_symbol' => env('A2_CURRENCY_SYMBOL', 'KSh'),

    // Currency conversion for PayPal (KES to USD)
    'currency_conversion_rate' => env('A2_CURRENCY_CONVERSION_RATE', 100), // 1 USD = 100 KES (adjust as needed)

    // Admin Email
    'admin_email' => env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),

    // Comparison Settings
    'comparison' => [
        // Priority keys determine the order of attributes in comparison table
        // Attributes listed here will appear first, in the order specified
        'priority_keys' => [
            // Meta keys (product meta attributes)
            'spare_part_number',
            // Taxonomy types (product taxonomies)
            'category',
            'make',
            'model',
            'year',
            'engine',
        ],
        // Exclude keys - these attributes will not be displayed in comparison table
        'exclude_keys' => [
            // Meta keys to exclude
            'main_image',
            'additional_photos',
            'sale_price',
            'company',
            'description',
            'original_product_url',
            'alternative_name',
            'acquiring_price',
            'acquiring_price_currency',
            'internal_notes',
            'admin_notes',
            'tag',
            // Taxonomy types to exclude (if any)
        ],
    ],
];
