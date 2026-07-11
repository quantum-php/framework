<?php

return [
    /**
     * ---------------------------------------------------------
     * Multilingual settings
     * ---------------------------------------------------------
     */
    'supported' => ['en', 'es'],
    'default' => 'file',
    'default_locale' => 'en',
    'file' => [],
    'deepl' => [
        'auth_key' => '',
        'source_locale' => null,
        'cache' => [
            'enabled' => true,
            'default' => 'file',
            'ttl' => 3600,
            'prefix' => 'lang_deepl:',
        ],
    ],
    'google_translate' => [
        'api_key' => '',
        'source_locale' => null,
        'cache' => [
            'enabled' => true,
            'default' => 'file',
            'ttl' => 3600,
            'prefix' => 'lang_google_translate:',
        ],
    ],
    'url_segment' => 1,
];
