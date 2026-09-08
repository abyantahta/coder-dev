<?php

return [

    // Defaults are the real WSA endpoint values (same ones prodhourlyreport
    // uses) — no .env entries are required for this to work.
    'url' => env('QAD_URL', 'http://qadeesdi.site:25079/wsa/wsaprod'),

    'ws_namespace' => env('QAD_WS_NAMESPACE', 'http://ws.imi.co.id/wsaprod'),

    'domain' => env('QAD_DOMAIN', '7000'),

    'timeout' => (int) env('QAD_TIMEOUT', 3600),

    'ssl_verify' => filter_var(env('QAD_SSL_VERIFY', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),

];
