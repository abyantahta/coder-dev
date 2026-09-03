<?php

return [

    'url' => env('QAD_URL', 'http://qadeesdi.site:24079/qxi/services/QdocWebService'),

    // TODO confirm exact namespace for this QXtend service group
    'ws_namespace' => env('QAD_WS_NAMESPACE', ''),

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
