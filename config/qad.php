<?php

return [

    // WSA broker (port 25079) — verified against prodhourlyreport/'s working
    // QAD sync, which calls the same SDI_getItemMasterExt operation.
    'url' => env('QAD_URL', 'http://qadeesdi.site:25079/wsa/wsaprod'),

    'ws_namespace' => env('QAD_WS_NAMESPACE', 'http://ws.imi.co.id/wsaprod'),

    'domain' => env('QAD_DOMAIN', '7000'),

    'username' => env('QAD_USERNAME'),
    'password' => env('QAD_PASSWORD'),
    'version' => env('QAD_VERSION', 'eB2_2'),

    // Only sync item masters whose t_pt_prod_line is in this list (comma-separated).
    // Empty = no filter (browse all active items).
    'prod_lines' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('QAD_ITEM_PROD_LINES', '')),
    ))),

    // Only sync item masters whose t_pt_group matches this value. Empty = no filter.
    'group_filter' => env('QAD_ITEM_GROUP') ?: null,

    'timeout' => (int) env('QAD_TIMEOUT', 120),

    'ssl_verify' => filter_var(env('QAD_SSL_VERIFY', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),

];
