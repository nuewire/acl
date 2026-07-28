<?php

declare(strict_types=1);

namespace Nuewire\Acl;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nuewire\Acl\Access\SpatieAccessManager;
use Nuewire\Acl\Commands\InstallCommand;
use Nuewire\Acl\Commands\SyncPermissionsCommand;
use Nuewire\Acl\Livewire\Acl;
use Nuewire\Acl\Livewire\UserAccess;
use Nuewire\Acl\Registry\PermissionRegistry;
use Nuewire\Acl\Support\AclAuthorizer;
use Nuewire\Acl\Support\AclState;
use Nuewire\Acl\Support\SpatieModels;
use Nuewire\Users\Contracts\AccessManager;

final class AclServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->replaceConfigRecursivelyFrom(__DIR__.'/../config/nuewire/acl.php', 'nuewire.acl');

        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(AclAuthorizer::class);
        $this->app->singleton(SpatieModels::class);
        $this->app->singleton(AclState::class);
        $this->app->singleton(AccessManager::class, SpatieAccessManager::class);
        $this->app->instance('nuewire.acl.enabled', true);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SyncPermissionsCommand::class,
            ]);
        }

        $this->registerPlatformNavigation();
        $this->registerCorePermissions();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nuewire-acl');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'nuewire-acl');
        $this->registerLivewireComponents();
        $this->registerSuperAdminGate();

        $this->publishes([
            __DIR__.'/../config/nuewire/acl.php' => config_path('nuewire/acl.php'),
        ], 'nuewire-acl-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/nuewire/acl'),
        ], 'nuewire-acl-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/nuewire/acl'),
        ], 'nuewire-acl-translations');
    }

    private function registerLivewireComponents(): void
    {
        $livewire = $this->app->make('livewire');

        if (method_exists($livewire, 'addComponent')) {
            $livewire->addComponent('nuewire::acl', null, Acl::class);
            $livewire->addComponent('nuewire::user-access', null, UserAccess::class);

            return;
        }

        Livewire::component('nuewire::acl', Acl::class);
        Livewire::component('nuewire::user-access', UserAccess::class);
    }

    private function registerPlatformNavigation(): void
    {
        $registryClass = 'Nuewire\\Platform\\Navigation\\NavigationRegistry';

        $this->app->afterResolving($registryClass, static function (object $registry): void {
            if (! method_exists($registry, 'register')) {
                return;
            }

            $registry->register('acl', [
                'label' => ['id' => 'Akses', 'en' => 'Access'],
                'description' => ['id' => 'Kelola role dan permission.', 'en' => 'Manage roles and permissions.'],
                'group' => ['id' => 'Manajemen', 'en' => 'Management'],
                'component' => 'nuewire::acl',
                'permission' => 'acl.view',
                'icon' => 'A',
                'order' => 20,
            ]);
        });
    }

    private function registerCorePermissions(): void
    {
        $this->app->afterResolving(PermissionRegistry::class, static function (PermissionRegistry $registry): void {
            $registry->registerMany([
                'acl.view' => ['id' => 'Melihat role dan permission', 'en' => 'View roles and permissions'],
                'acl.manage' => ['id' => 'Mengelola role dan permission', 'en' => 'Manage roles and permissions'],
            ], 'core');
        });
    }

    private function registerSuperAdminGate(): void
    {
        Gate::before(static function (object $user, string $ability): ?bool {
            $state = app(AclState::class);

            if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable
                && $state->simpleAdminFallback($user)
                && in_array($ability, app(PermissionRegistry::class)->names(), true)) {
                return true;
            }

            if (! $state->ready()) {
                return null;
            }

            $role = (string) config('nuewire.acl.super_admin_role', 'super-admin');

            try {
                return method_exists($user, 'hasRole') && $user->hasRole($role) ? true : null;
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
