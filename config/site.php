<?php

return [
    'name' => 'An Duong ITI',
    'legal_name' => 'An Duong International Trade & Investment Co., Ltd',
    'url' => env('APP_URL', 'https://anduongiti.com'),
    'contact_throttle_limit' => (int) env('CONTACT_THROTTLE_LIMIT', 10),
    // Production remains five attempts/minute; browser test environments may
    // raise this only through environment variables.
    'login_throttle_limit' => (int) env('LOGIN_THROTTLE_LIMIT', 5),
    'tagline' => [
        'en' => 'Creating connections, sustaining value',
        'vi' => 'Kết nối giá trị, phát triển bền vững',
    ],
    'founded' => 2020,
    'profile_pdf' => [
        'href' => '/downloads/anduongiti-company-profile.pdf',
        'cover' => '/downloads/profile-cover.png',
        'pages' => 14,
        'size' => '14 MB',
    ],

    'nav' => [
        ['key' => 'about',          'href' => '/about'],
        ['key' => 'products',       'href' => '/products'],
        ['key' => 'coffee',         'href' => '/coffee'],
        ['key' => 'sustainability', 'href' => '/sustainability'],
        ['key' => 'partners',       'href' => '/partners'],
        ['key' => 'news',           'href' => '/news'],
        ['key' => 'support',        'href' => '/support'],
        ['key' => 'contact',        'href' => '/contact'],
    ],
];
