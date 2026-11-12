<?php

return [

    'timeout' => env('BRAND_API_TIMEOUT', 30),

    'items' => [

        'dental' => [
            'label' => 'Dental',
            'url'   => env('BRAND_DENTAL_URL'),
            'token' => env('BRAND_DENTAL_TOKEN'),
        ],

        'aesthetics' => [
            'label' => 'Aesthetics',
            'url'   => env('BRAND_AESTHETICS_URL'),
            'token' => env('BRAND_AESTHETICS_TOKEN'),
        ],

        'hair' => [
            'label' => 'Hair',
            'url'   => env('BRAND_HAIR_URL'),
            'token' => env('BRAND_HAIR_TOKEN'),
        ],

        'ortho' => [
            'label' => 'Ortho',
            'url'   => env('BRAND_ORTHO_URL'),
            'token' => env('BRAND_ORTHO_TOKEN'),
        ],

        'nextcell' => [
            'label' => 'NextCell',
            'url'   => env('BRAND_NEXTCELL_URL'),
            'token' => env('BRAND_NEXTCELL_TOKEN'),
        ],

    ],

];
