<?php

declare(strict_types=1);

namespace Nuewire\Acl\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Nuewire\Acl\Support\AclState;
use Nuewire\Users\Access\SimpleAccessManager;
use Nuewire\Users\Contracts\AccessManager;
use Throwable;

final class SpatieAccessManager implements AccessManager
{
    public function __construct(
        private readonly AclState $state,
        private readonly SimpleAccessManager $simple,
    ) {
    }

    public function mode(): string
    {
        return $this->state->ready() ? 'acl' : 'simple';
    }

    public function allows(Authenticatable $actor, string $ability, ?Model $subject = null): bool
    {
        if (! $this->state->ready()) {
            return $this->simple->allows($actor, $ability, $subject);
        }

        $super = (string) config('nuewire.acl.super_admin_role', 'super-admin');

        try {
            if (method_exists($actor, 'hasRole') && $actor->hasRole($super)) {
                return true;
            }

            return method_exists($actor, 'can') && $actor->can($ability);
        } catch (Throwable) {
            return false;
        }
    }

    public function save(Model $user, array $payload): void
    {
        if (! $this->state->ready()) {
            $this->simple->save($user, $payload);

            return;
        }

        // ACL assignment is handled by nuewire::user-access after ACL is ready.
    }

    public function summary(Model $user): array
    {
        if (! $this->state->ready()) {
            return $this->simple->summary($user);
        }

        try {
            return method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()->values()->all()
                : [];
        } catch (Throwable) {
            return [];
        }
    }

    public function component(): ?string
    {
        return $this->state->ready() ? 'nuewire::user-access' : null;
    }

    public function assertCanDelete(Model $user, Authenticatable $actor): void
    {
        if (! $this->state->ready()) {
            $this->simple->assertCanDelete($user, $actor);

            return;
        }

        $super = (string) config('nuewire.acl.super_admin_role', 'super-admin');

        if (! method_exists($user, 'hasRole') || ! $user->hasRole($super)) {
            return;
        }

        $remaining = $user->newQuery()
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', static fn ($query) => $query->where('name', $super))
            ->exists();

        if (! $remaining) {
            throw ValidationException::withMessages([
                'user' => __('nuewire-acl::acl.validation.last_super_admin'),
            ]);
        }
    }
}
