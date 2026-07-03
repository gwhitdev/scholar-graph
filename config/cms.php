<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reserved URL Slugs
    |--------------------------------------------------------------------------
    |
    | These slugs are reserved for application routes and cannot be used when
    | creating CMS pages. The public catch-all route must not shadow them.
    |
    */

    'reserved' => [
        'projects',
        'admin',
        'settings',
        'support',
        'help',
        'login',
        'register',
        'dashboard',
        'up',
        'storage',
        'api',
        'user',
        'password',
        'email',
        'verify',
        'reset',
        'two-factor',
        'teams',
        'billing',
        'collections',
        'syntheses',
        'papers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Block Types
    |--------------------------------------------------------------------------
    |
    | Block types that can be used in page content arrays.
    |
    */

    'block_types' => [
        'heading',
        'paragraph',
        'image',
        'cta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Upload Rules
    |--------------------------------------------------------------------------
    */

    'media' => [
        'max_size' => 10240, // KB (10 MB)
        'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'],
    ],

];
