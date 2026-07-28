<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Nuewire\Acl\Registry\PermissionRegistry;

final class PermissionRegistryTest extends TestCase
{
    public function test_packages_can_register_permissions(): void
    {
        $registry = app(PermissionRegistry::class);
        $registry->register('example.view', ['id' => 'Lihat contoh', 'en' => 'View example'], 'example');

        self::assertContains('example.view', $registry->names());
        self::assertSame('Lihat contoh', $registry->all('id')['example.view']['label']);
    }
}
