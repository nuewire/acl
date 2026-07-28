<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Illuminate\Support\ServiceProvider;
use Nuewire\Acl\AclServiceProvider;

final class PublishPathTest extends TestCase
{
    public function test_resources_use_shared_nuewire_vendor_directories(): void
    {
        self::assertContains(
            resource_path('views/vendor/nuewire/acl'),
            array_values(ServiceProvider::pathsToPublish(AclServiceProvider::class, 'nuewire-acl-views')),
        );
        self::assertContains(
            lang_path('vendor/nuewire/acl'),
            array_values(ServiceProvider::pathsToPublish(AclServiceProvider::class, 'nuewire-acl-translations')),
        );
    }
}
