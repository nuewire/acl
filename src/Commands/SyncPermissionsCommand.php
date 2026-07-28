<?php

declare(strict_types=1);

namespace Nuewire\Acl\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Nuewire\Acl\Registry\PermissionRegistry;
use Nuewire\Acl\Support\AclState;
use Nuewire\Acl\Support\SpatieModels;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'nuewire:acl:sync';

    protected $description = 'Synchronize registered Nuewire roles and permissions';

    public function handle(PermissionRegistry $registry, PermissionRegistrar $registrar, AclState $state, SpatieModels $spatie): int
    {
        $guard = (string) config('nuewire.acl.guard_name', 'web');

        if (! Schema::hasTable((string) config('permission.table_names.roles', 'roles'))
            || ! Schema::hasTable((string) config('permission.table_names.permissions', 'permissions'))) {
            $this->components->error('Tabel permission belum tersedia. Jalankan migrasi.');

            return self::FAILURE;
        }

        try {
            foreach ($registry->names() as $name) {
                $spatie->findOrCreatePermission($name, $guard);
            }

            $superAdmin = $spatie->findOrCreateRole((string) config('nuewire.acl.super_admin_role', 'super-admin'), $guard);

            if ((bool) config('nuewire.acl.sync.grant_all_to_super_admin', true)) {
                $superAdmin->syncPermissions($spatie->permissions()->where('guard_name', $guard)->get());
            }

            if ((bool) config('nuewire.acl.sync.create_administrator_role', true)) {
                $spatie->findOrCreateRole((string) config('nuewire.acl.administrator_role', 'administrator'), $guard);
            }

            $registrar->forgetCachedPermissions();
            $state->forget();
            $this->components->info('Permission Nuewire disinkronkan.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
