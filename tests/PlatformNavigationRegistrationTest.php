<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

final class PlatformNavigationRegistrationTest extends TestCase
{
    public function test_roles_page_is_registered_before_users(): void
    {
        $abstract = 'Nuewire\\Platform\\Navigation\\NavigationRegistry';
        $this->app->singleton($abstract, static fn (): FakeAclNavigationRegistry => new FakeAclNavigationRegistry());

        /** @var FakeAclNavigationRegistry $registry */
        $registry = $this->app->make($abstract);

        self::assertArrayHasKey('acl.roles', $registry->pages);
        self::assertSame('settings', $registry->pages['acl.roles']['area']);
        self::assertSame('user-management', $registry->pages['acl.roles']['group']);
        self::assertSame('roles', $registry->pages['acl.roles']['slug']);
        self::assertContains('acl', $registry->pages['acl.roles']['aliases']);
    }
}

final class FakeAclNavigationRegistry
{
    /** @var array<string, array<string, mixed>> */
    public array $pages = [];

    /** @param array<string, mixed> $area */
    public function registerArea(string $id, array $area = []): self
    {
        return $this;
    }

    /** @param array<string, mixed> $page */
    public function register(string $id, array $page): self
    {
        $this->pages[$id] = $page;

        return $this;
    }
}
