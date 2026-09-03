<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QAD SOAP endpoint
    |--------------------------------------------------------------------------
    |
    | URL used for WSA SOAP POST (same as webprod `api_sync_qad`).
    | Namespace URI goes into the SOAP envelope xmlns (webprod `api_sync_qad_ws`).
    |
    */

    'url' => env('QAD_SYNC_URL', 'http://qadeesdi.site:25079/wsa/wsaprod'),

    'ws_namespace' => env('QAD_SYNC_WS', 'http://ws.imi.co.id/wsaprod'),

    'domain' => env('QAD_SYNC_DOMAIN', '7000'),

    /*
    | Only sync item masters whose t_pt_prod_line is in this list (comma-separated).
    | Matches webprod `sync_prod_line` (FG, SA).
    */
    'prod_lines' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('QAD_SYNC_PROD_LINES', 'FG,SA')),
    ))),

    // SOAP HTTP timeout and PHP max_execution_time for sync jobs (seconds).
    'timeout' => (int) env('QAD_SYNC_TIMEOUT', 3600),

    /*
    | SSL peer verification for the SOAP HTTP client.
    | Keep false for local/dev hosts with self-signed certs; enable in production.
    */
    'ssl_verify' => filter_var(env('QAD_SYNC_SSL_VERIFY', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),

];
