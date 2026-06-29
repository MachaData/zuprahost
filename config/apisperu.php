<?php

return [
    /*
    |--------------------------------------------------------------------------
    | APISPERU - Facturación electrónica SUNAT
    |--------------------------------------------------------------------------
    |
    | Integración con https://apisperu.com para emitir comprobantes
    | electrónicos (boletas y facturas) ante SUNAT.
    |
    */

    // Token JWT entregado por APISPERU.
    'token' => env('APISPERU_TOKEN'),

    // URL base del servicio de facturación.
    'base_url' => env('APISPERU_BASE_URL', 'https://facturacion.apisperu.com/api/v1'),

    // Datos de la empresa emisora.
    'company' => [
        'ruc' => env('APISPERU_COMPANY_RUC'),
        'razon_social' => env('APISPERU_COMPANY_NAME', 'zupraHost'),
        'nombre_comercial' => env('APISPERU_COMPANY_TRADE_NAME', 'zupraHost'),
        'address' => [
            'direccion' => env('APISPERU_COMPANY_ADDRESS', ''),
            'ubigueo' => env('APISPERU_COMPANY_UBIGEO', '150101'),
            'departamento' => env('APISPERU_COMPANY_DEPARTMENT', 'LIMA'),
            'provincia' => env('APISPERU_COMPANY_PROVINCE', 'LIMA'),
            'distrito' => env('APISPERU_COMPANY_DISTRICT', 'LIMA'),
            'cod_local' => env('APISPERU_COMPANY_LOCAL', '0000'),
        ],
    ],

    // Porcentaje de IGV.
    'igv_rate' => (float) env('APISPERU_IGV_RATE', 0.18),

    // Modo: cuando está deshabilitado no se hacen llamadas reales (útil en dev).
    'enabled' => (bool) env('APISPERU_ENABLED', false),
];
