<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Nuewire\Acl\Access\SpatieAccessManager;
use Nuewire\Acl\Support\AclState;
use Nuewire\Users\Contracts\AccessManager;

final class BindingTest extends TestCase
{
    public function test_acl_replaces_the_simple_access_manager(): void
    {
        self::assertInstanceOf(SpatieAccessManager::class, app(AccessManager::class));
        self::assertTrue(app()->bound('nuewire.acl.enabled'));
    }

    public function test_acl_falls_back_to_simple_mode_before_initialization(): void
    {
        self::assertFalse(app(AclState::class)->ready());
        self::assertSame('simple', app(AccessManager::class)->mode());
    }
}
