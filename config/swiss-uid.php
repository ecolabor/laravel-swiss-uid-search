<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UID Webservice Version 5.0
    |--------------------------------------------------------------------------
    |
    | The WSDL endpoints for the Swiss UID (Unternehmens-Identifikationsnummer)
    | webservice provided by the Federal Statistical Office (BFS).
    |
    | Documentation: https://www.bfs.admin.ch/bfs/de/home/register/unternehmensregister/unternehmens-identifikationsnummer.html
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Environment (production or test)
    |--------------------------------------------------------------------------
    */
    'environment' => env('SWISS_UID_ENVIRONMENT', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Public Services WSDL URLs
    |--------------------------------------------------------------------------
    |
    | Public Services are available without authentication.
    | Operations: Search, GetByUID, ValidateUID, ValidateVatNumber
    |
    */
    'public_wsdl' => [
        'production' => 'https://www.uid-wse.admin.ch/V5.0/PublicServices.svc?wsdl',
        'test' => 'https://www.uid-wse-a.admin.ch/V5.0/PublicServices.svc?wsdl',
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Services WSDL URLs
    |--------------------------------------------------------------------------
    |
    | Partner Services require authentication (username_sa + password).
    | Contact uid@bfs.admin.ch for access.
    |
    */
    'partner_wsdl' => [
        'production' => 'https://www.uid-wse.admin.ch/V5.0/PartnerServices.svc?wsdl',
        'test' => 'https://www.uid-wse-a.admin.ch/V5.0/PartnerServices.svc?wsdl',
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Services Authentication
    |--------------------------------------------------------------------------
    |
    | Credentials for Partner Services (username ends with _sa suffix).
    |
    */
    'partner_auth' => [
        'username' => env('SWISS_UID_PARTNER_USERNAME'),
        'password' => env('SWISS_UID_PARTNER_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP Client Options
    |--------------------------------------------------------------------------
    */
    'soap_options' => [
        'trace' => env('SWISS_UID_SOAP_TRACE', false),
        'exceptions' => true,
        'cache_wsdl' => env('SWISS_UID_CACHE_WSDL', WSDL_CACHE_BOTH),
        'connection_timeout' => env('SWISS_UID_CONNECTION_TIMEOUT', 30),
        'soap_version' => SOAP_1_1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language for API responses.
    | Supported: 1 = German, 2 = French, 3 = Italian, 4 = English
    |
    */
    'language' => env('SWISS_UID_LANGUAGE', 1), // 1 = German

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    */
    'search' => [
        'max_results' => env('SWISS_UID_MAX_RESULTS', 100),
        'search_mode' => env('SWISS_UID_SEARCH_MODE', 'auto'), // auto, exact, wild
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SWISS_UID_CACHE_ENABLED', true),
        'ttl' => env('SWISS_UID_CACHE_TTL', 3600),
        'prefix' => 'swiss_uid_',
    ],
];
