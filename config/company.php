<?php

// Datos del taller para los comprobantes imprimibles y los reportes exportados.
return [

    'name' => env('COMPANY_NAME', env('APP_NAME', 'Taller')),
    'legal_name' => env('COMPANY_LEGAL_NAME'),
    'tax_id' => env('COMPANY_TAX_ID'),
    'activity' => env('COMPANY_ACTIVITY'),
    'address' => env('COMPANY_ADDRESS'),
    'city' => env('COMPANY_CITY'),
    'phone' => env('COMPANY_PHONE'),
    'email' => env('COMPANY_EMAIL'),

];
