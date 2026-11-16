<?php

return [
    'supportedLocales' => [
        'en' => ['name' => 'English',    'script' => 'Latn', 'native' => 'English',    'regional' => 'en_GB'],
        'vi' => ['name' => 'Vietnamese', 'script' => 'Latn', 'native' => 'Tiếng Việt', 'regional' => 'vi_VN'],
    ],

    // EN at `/`, VI at `/vi/...`. We deliberately leave Accept-Language
    // detection OFF so a Vietnamese-speaking browser doesn't get
    // hard-redirected away from `/` (the EN site is the marketing
    // primary and the user opts into VI via the header switcher).
    'useAcceptLanguageHeader' => false,
    'hideDefaultLocaleInURL' => true,
    'localesOrder' => ['en', 'vi'],
    'localesMapping' => [],
    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),
    'urlsIgnored' => ['/admin', '/admin/*', '/livewire/*', '/sitemap.xml', '/robots.txt'],
];
