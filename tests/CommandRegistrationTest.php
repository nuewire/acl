<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Illuminate\Support\Facades\Artisan;

final class CommandRegistrationTest extends TestCase
{
    public function test_acl_commands_are_registered(): void
    {
        self::assertTrue(Artisan::has('nuewire:acl:install'));
        self::assertTrue(Artisan::has('nuewire:acl:sync'));
    }
}
