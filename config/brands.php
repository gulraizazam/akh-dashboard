<?php

return [

    /*
     | Shared credentials for the brand databases. All six brand apps live on
     | the same MySQL server, so one read-only user granted SELECT on each
     | database is enough. Per-brand overrides exist below for the transition
     | period, before that user is created.
     */
    'db' => [
        'host'     => env('BRAND_DB_HOST', '127.0.0.1'),
        'port'     => env('BRAND_DB_PORT', '3306'),
        'username' => env('BRAND_DB_USERNAME'),
        'password' => env('BRAND_DB_PASSWORD'),
    ],

    /*
     | Pseudo-rows in every brand's `locations` table that stand for
     | aggregates rather than real centres. The brand apps exclude these from
     | their own dashboards, so we do too.
     */
    'excluded_locations' => [
        'All Centres',
        'All Central Region',
        'All South Region',
    ],

    /*
     | Values that are identical across all six brand databases (verified).
     */
    'account_id'              => 1, // every brand DB has exactly one account
    'invoice_status_paid'     => 3, // invoice_statuses.slug = 'paid'
    'appointment_type_consult' => 1,
    'appointment_type_service' => 2,
    'user_type_patient'       => 3,

    'items' => [

        'american' => [
            'label'    => 'American Aesthetics',
            'site'     => 'https://american.nexthealth.com.pk',
            'branch'   => 'main',
            'database' => env('BRAND_AMERICAN_DB', 'u274270318_american'),
            'username' => env('BRAND_AMERICAN_DB_USER'),
            'password' => env('BRAND_AMERICAN_DB_PASS'),
        ],

        'hair' => [
            'label'    => 'Alkhaleej Hair Transplant',
            'site'     => 'https://hair.nexthealth.com.pk',
            'branch'   => 'hair',
            'database' => env('BRAND_HAIR_DB', 'u274270318_hairtransplant'),
            'username' => env('BRAND_HAIR_DB_USER'),
            'password' => env('BRAND_HAIR_DB_PASS'),
        ],

        'ortho' => [
            'label'    => 'OrthoStem',
            'site'     => 'https://ortho.nexthealth.com.pk',
            'branch'   => 'ortho_new',
            'database' => env('BRAND_ORTHO_DB', 'u274270318_orthostem'),
            'username' => env('BRAND_ORTHO_DB_USER'),
            'password' => env('BRAND_ORTHO_DB_PASS'),
        ],

        'smile' => [
            'label'    => 'Smile',
            'site'     => 'https://smile.nexthealth.com.pk',
            'branch'   => 'dental',
            'database' => env('BRAND_SMILE_DB', 'u274270318_dental1'),
            'username' => env('BRAND_SMILE_DB_USER'),
            'password' => env('BRAND_SMILE_DB_PASS'),
        ],

        'skin' => [
            'label'    => 'Alkhaleej Skin Clinics',
            'site'     => 'https://skin.nexthealth.com.pk',
            'branch'   => 'aesthetics',
            'database' => env('BRAND_SKIN_DB', 'u274270318_aesthetics'),
            'username' => env('BRAND_SKIN_DB_USER'),
            'password' => env('BRAND_SKIN_DB_PASS'),
        ],

        'nexthealth' => [
            'label'    => 'NextHealth',
            'site'     => 'https://crm.nexthealth.com.pk',
            'branch'   => 'nextcell',
            'database' => env('BRAND_NEXTHEALTH_DB', 'u274270318_nextcell'),
            'username' => env('BRAND_NEXTHEALTH_DB_USER'),
            'password' => env('BRAND_NEXTHEALTH_DB_PASS'),
        ],

    ],

];
