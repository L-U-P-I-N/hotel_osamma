<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base currency / العملة الأساسية
    |--------------------------------------------------------------------------
    | Ledger reporting currency. Journal lines may carry a foreign currency
    | plus an exchange rate, but balances are reported in this one.
    */
    'base_currency' => env('HOTEL_BASE_CURRENCY', 'YER'),

    'currencies' => [
        'YER' => ['symbol_en' => 'YER', 'symbol_ar' => 'ر.ي', 'decimals' => 2],
        'SAR' => ['symbol_en' => 'SAR', 'symbol_ar' => 'ر.س', 'decimals' => 2],
        'USD' => ['symbol_en' => 'USD', 'symbol_ar' => '$',   'decimals' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chart of accounts / شجرة الحسابات
    |--------------------------------------------------------------------------
    | Rounding tolerance when checking that a journal entry balances. Money is
    | stored to 2 decimals, so anything under half a minor unit is float noise
    | rather than a real imbalance.
    */
    'coa' => [
        'balance_tolerance' => 0.005,
        'max_level'         => 4,
    ],
];
