<?php

declare(strict_types=1);

namespace Nuewire\Acl\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AclState
{
    private ?bool $ready = null;

    public function __construct(private readonly SpatieModels $models)
    {
    }

    public function ready(): bool
    {
        if ($this->ready !== null) {
            return $this->ready;
        }

        try {
            $tables = (array) config('permission.table_names', []);

            if (! Schema::hasTable((string) ($tables['roles'] ?? 'roles'))
                || ! Schema::hasTable((string) ($tables['permissions'] ?? 'permissions'))
                || ! Schema::hasTable((string) ($tables['model_has_roles'] ?? 'model_has_roles'))) {
                return $this->ready = false;
            }

            $role = $this->models->roles()
                ->where('guard_name', (string) config('nuewire.acl.guard_name', 'web'))
                ->where('name', (string) config('nuewire.acl.super_admin_role', 'super-admin'))
                ->first();

            return $this->ready = $role !== null && $role->users()->exists();
        } catch (Throwable) {
            return $this->ready = false;
        }
    }

    public function simpleAdminFallback(Authenticatable $user): bool
    {
        if (! (bool) config('nuewire.acl.fallback_to_simple_admin_until_ready', true) || $this->ready()) {
            return false;
        }

        $field = (string) config('nuewire.users.fields.is_admin', 'is_admin');

        return (bool) data_get($user, $field, false);
    }

    public function forget(): void
    {
        $this->ready = null;
    }
}
