<?php

return [

    // Port 24079 + wsatest (not 25079/wsaprod) — url and namespace change
    // together as a matched pair.
    'url' => env('QAD_URL', 'http://qadeesdi.site:24079/wsa/wsatest'),

    'ws_namespace' => env('QAD_WS_NAMESPACE', 'http://ws.imi.co.id/wsatest'),

    'domain' => env('QAD_DOMAIN', '7000'),

    'timeout' => (int) env('QAD_TIMEOUT', 3600),

    'ssl_verify' => filter_var(env('QAD_SSL_VERIFY', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),

    // Prod lines skipped when syncing the local item master (warehouse PR picker).
    'excluded_prod_lines' => ['FG', 'RM', 'SA'],

];
