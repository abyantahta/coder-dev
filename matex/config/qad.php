<?php

return [

    // QXI/QDoc gateway (port 24079) — generic SOAP dispatcher used for
    // transactional maintain* operations (maintainPurchaseOrder,
    // receivePurchaseOrder). Routes internally by the wsa:To header to a
    // company-specific SDI_ program. This is QAD's TEST environment.
    'qxi' => [
        'base_url' => env('QAD_BASE_URL'),
        'domain' => env('QAD_DOMAIN', '7000'),
        'version' => env('QAD_VERSION', 'eB2_3'),
        'username' => env('QAD_USERNAME'),
        'password' => env('QAD_PASSWORD'),
    ],

    // WSA (Web Service Adapter, port 25079) — read-only master data queries
    // (item master, supplier master). Unauthenticated. This is QAD's
    // PRODUCTION environment — distinct from the qxi test instance above.
    'wsa' => [
        'url' => env('QAD_WSA_URL', 'http://qadeesdi.site:25079/wsa/wsaprod'),
        'namespace' => env('QAD_WS_NAMESPACE', 'http://ws.imi.co.id/wsaprod'),
        'timeout' => (int) env('QAD_WSA_TIMEOUT', 3600),
        'ssl_verify' => filter_var(env('QAD_SSL_VERIFY', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
    ],

    // Business defaults shared by PO creation (maintainPurchaseOrder) and
    // receiving (receivePurchaseOrder) — every matex PO/receipt uses the
    // same site, location, GL account etc, so these are one config knob
    // instead of string literals copy-pasted across both services.
    'defaults' => [
        'site' => env('QAD_SITE', '7101'),
        'location' => env('QAD_LOCATION', 'RAWMAT'),
        'po_type' => env('QAD_PO_TYPE', 'P'),
        'account' => env('QAD_ACCOUNT', '504006'),
        'daybookset' => env('QAD_DAYBOOKSET', 'AP-SET'),
        'credit_terms' => env('QAD_CREDIT_TERMS', '45D'),
        'currency' => env('QAD_CURRENCY', 'IDR'),
        'tax_code' => env('QAD_TAX_CODE', 'PPN'),
        'tax_env' => env('QAD_TAX_ENV', 'IDN'),
        'requester_id' => env('QAD_REQUESTER_ID', 'MFG'),
        // matex doesn't model purchase price yet — QAD still requires a
        // non-zero podPurCost per line, so this is a placeholder value
        // until real pricing is tracked.
        'purchase_cost' => env('QAD_PURCHASE_COST', '450.00000'),
    ],

    // receivePurchaseOrder (SDI_eKanbanGR) had a confirmed silent no-op
    // (QAD replied result=success but qty never actually posted) — root
    // cause was the request payload shape, since fixed and verified
    // end-to-end against real QAD (see docs/qad-verification-request.md).
    // Kept as a flag, checked in DeliveryNotePolicy + ReceiveToQad, so
    // receiving can be paused again instantly (no redeploy) if needed.
    'receiving_enabled' => env('QAD_RECEIVING_ENABLED', true),

];
