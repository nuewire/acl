<?php

declare(strict_types=1);

return [
    'locale' => env('NUEWIRE_ACL_LOCALE', 'id'),
    'supported_locales' => ['id', 'en'],
    'guard_name' => env('NUEWIRE_ACL_GUARD', 'web'),
    'super_admin_role' => env('NUEWIRE_ACL_SUPER_ADMIN_ROLE', 'super-admin'),
    'administrator_role' => env('NUEWIRE_ACL_ADMIN_ROLE', 'administrator'),
    'fallback_to_simple_admin_until_ready' => true,
    'authorization' => [
        'require_authenticated_user' => true,
        'guard' => null,
        'gate' => null,
    ],
    'migration' => [
        'grant_current_permissions_to_administrator' => true,
    ],
    'sync' => [
        'create_administrator_role' => true,
        'grant_all_to_super_admin' => true,
    ],
];
