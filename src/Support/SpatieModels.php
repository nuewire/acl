<?php

declare(strict_types=1);

namespace Nuewire\Acl\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SpatieModels
{
    /** @return class-string<Model> */
    public function roleClass(): string
    {
        /** @var class-string<Model> $class */
        $class = (string) config('permission.models.role', Role::class);

        return $class;
    }

    /** @return class-string<Model> */
    public function permissionClass(): string
    {
        /** @var class-string<Model> $class */
        $class = (string) config('permission.models.permission', Permission::class);

        return $class;
    }

    /** @return Builder<Model> */
    public function roles(): Builder
    {
        $class = $this->roleClass();

        return $class::query();
    }

    /** @return Builder<Model> */
    public function permissions(): Builder
    {
        $class = $this->permissionClass();

        return $class::query();
    }

    public function newRole(): Model
    {
        $class = $this->roleClass();

        return new $class();
    }

    public function findOrCreateRole(string $name, string $guard): Model
    {
        $class = $this->roleClass();

        return $class::findOrCreate($name, $guard);
    }

    public function findOrCreatePermission(string $name, string $guard): Model
    {
        $class = $this->permissionClass();

        return $class::findOrCreate($name, $guard);
    }
}
