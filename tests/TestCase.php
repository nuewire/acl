<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Livewire\LivewireServiceProvider;
use Nuewire\Acl\AclServiceProvider;
use Nuewire\Users\UsersServiceProvider;
use Nuewire\Support\SupportServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SupportServiceProvider::class,
            LivewireServiceProvider::class,
            PermissionServiceProvider::class,
            UsersServiceProvider::class,
            AclServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
