<?php

declare(strict_types=1);

namespace Nuewire\Acl\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Nuewire\Acl\Support\AclState;
use Nuewire\Acl\Support\SpatieModels;
use Nuewire\Users\Support\UserModelResolver;
use Spatie\Permission\PermissionServiceProvider;
use Throwable;

final class InstallCommand extends Command
{
    protected $signature = 'nuewire:acl:install
        {--migrate : Run database migrations}
        {--user= : User ID or email that receives the super-admin role}
        {--force : Overwrite published Spatie files}';

    protected $description = 'Install Nuewire ACL and Spatie permissions';

    public function handle(UserModelResolver $users, AclState $state, SpatieModels $spatie): int
    {
        $arguments = ['--provider' => PermissionServiceProvider::class];

        if ($this->option('force')) {
            $arguments['--force'] = true;
        }

        if ($this->call('vendor:publish', $arguments) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $model = $users->class();
        $hasTrait = in_array(\Nuewire\Acl\Concerns\HasNuewireAcl::class, class_uses_recursive($model), true);

        if (! $hasTrait) {
            $this->components->warn("Tambahkan trait Nuewire\\Acl\\Concerns\\HasNuewireAcl pada {$model}.");
        } else {
            $this->components->info('Trait HasNuewireAcl terdeteksi.');
        }

        if ($this->option('migrate') && $this->call('migrate', ['--force' => (bool) $this->option('force')]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->permissionTablesExist()) {
            $this->components->warn('Tabel permission belum tersedia. Jalankan php artisan migrate lalu php artisan nuewire:acl:install --user=EMAIL.');

            return self::SUCCESS;
        }

        if ($this->call('nuewire:acl:sync') !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $hasTrait) {
            $this->components->warn('Role belum ditetapkan karena model pengguna belum memakai HasNuewireAcl.');

            return self::SUCCESS;
        }

        try {
            $simpleAdmins = $this->simpleAdministrators($users);
            $this->migrateSimpleAdministrators($simpleAdmins, $spatie);
            $superAdmin = $this->resolveSuperAdmin($users, $simpleAdmins);

            if ($superAdmin instanceof Model && method_exists($superAdmin, 'assignRole')) {
                $superAdmin->assignRole((string) config('nuewire.acl.super_admin_role', 'super-admin'));
                $state->forget();
                $this->components->info('Role super-admin ditetapkan.');
            } else {
                $this->components->warn('Super admin belum ditetapkan. Jalankan ulang dengan --user=EMAIL.');
            }
        } catch (Throwable $exception) {
            $this->components->warn($exception->getMessage());
        }

        $this->components->info('Nuewire ACL siap.');

        return self::SUCCESS;
    }

    private function permissionTablesExist(): bool
    {
        return Schema::hasTable((string) config('permission.table_names.roles', 'roles'))
            && Schema::hasTable((string) config('permission.table_names.permissions', 'permissions'))
            && Schema::hasTable((string) config('permission.table_names.model_has_roles', 'model_has_roles'));
    }

    /** @return Collection<int, Model> */
    private function simpleAdministrators(UserModelResolver $users): Collection
    {
        $model = $users->new();
        $field = (string) config('nuewire.users.fields.is_admin', 'is_admin');

        if (! Schema::hasColumn($model->getTable(), $field)) {
            return $model->newCollection();
        }

        return $model->newQuery()->where($field, true)->get();
    }

    /** @param Collection<int, Model> $users */
    private function migrateSimpleAdministrators(Collection $users, SpatieModels $spatie): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $guard = (string) config('nuewire.acl.guard_name', 'web');
        $role = $spatie->findOrCreateRole((string) config('nuewire.acl.administrator_role', 'administrator'), $guard);

        if ((bool) config('nuewire.acl.migration.grant_current_permissions_to_administrator', true)) {
            $role->syncPermissions($spatie->permissions()->where('guard_name', $guard)->get());
        }

        foreach ($users as $user) {
            if (method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            }
        }

        $this->components->info($users->count().' administrator sederhana dimigrasikan.');
    }

    /** @param Collection<int, Model> $simpleAdmins */
    private function resolveSuperAdmin(UserModelResolver $users, Collection $simpleAdmins): ?Model
    {
        $value = trim((string) $this->option('user'));
        $model = $users->new();
        $email = (string) config('nuewire.users.fields.email', 'email');

        if ($value !== '') {
            return $model->newQuery()->whereKey($value)->orWhere($email, $value)->firstOrFail();
        }

        return $simpleAdmins->first();
    }
}
