<?php

declare(strict_types=1);

namespace Nuewire\Acl\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Nuewire\Acl\Support\AclAuthorizer;
use Nuewire\Acl\Support\AclState;
use Nuewire\Acl\Support\SpatieModels;
use Nuewire\Users\Support\UserModelResolver;
use Spatie\Permission\PermissionRegistrar;

final class UserAccess extends Component
{
    public string $userId = '';
    public string $locale = 'id';

    /** @var array<int, string> */
    public array $selectedRoles = [];

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public string $status = '';

    public function mount(string|int $userId, ?string $locale = null): void
    {
        app(AclAuthorizer::class)->authorize('users.assign-roles');
        abort_unless(app(AclState::class)->ready(), 503, 'Nuewire ACL has not been initialized.');
        $this->userId = (string) $userId;
        $this->locale = $this->resolveLocale($locale);
        $this->loadAssignment(app(UserModelResolver::class)->find($this->userId));
    }

    public function save(UserModelResolver $users, PermissionRegistrar $registrar, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('users.assign-roles');
        abort_unless(app(AclState::class)->ready(), 503, 'Nuewire ACL has not been initialized.');
        $user = $users->find($this->userId);

        if (! method_exists($user, 'syncRoles') || ! method_exists($user, 'syncPermissions')) {
            throw ValidationException::withMessages(['access' => $this->t('validation.missing_trait')]);
        }

        $guard = (string) config('nuewire.acl.guard_name', 'web');
        $this->validate([
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['string'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string'],
        ]);

        $this->assertLastSuperAdmin($user);
        $roles = $spatie->roles()->where('guard_name', $guard)->whereIn('name', $this->selectedRoles)->pluck('name')->all();
        $permissions = $spatie->permissions()->where('guard_name', $guard)->whereIn('name', $this->selectedPermissions)->pluck('name')->all();

        $user->syncRoles($roles);
        $user->syncPermissions($permissions);
        $registrar->forgetCachedPermissions();
        $this->loadAssignment($user->refresh());
        $this->status = $this->t('status.user_access_saved');
        $this->dispatch('nuewire-user-access-updated');
    }

    public function render(UserModelResolver $users, SpatieModels $spatie)
    {
        app(AclAuthorizer::class)->authorize('users.assign-roles');
        abort_unless(app(AclState::class)->ready(), 503, 'Nuewire ACL has not been initialized.');
        $user = $users->find($this->userId);
        $guard = (string) config('nuewire.acl.guard_name', 'web');

        return view('nuewire-acl::livewire.user-access', [
            'user' => $user,
            'roles' => $spatie->roles()->where('guard_name', $guard)->orderBy('name')->get(),
            'permissions' => $spatie->permissions()->where('guard_name', $guard)->orderBy('name')->get(),
            'effectivePermissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->sort()->values() : collect(),
        ]);
    }

    private function loadAssignment(Model $user): void
    {
        $this->selectedRoles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];
        $this->selectedPermissions = method_exists($user, 'getDirectPermissions') ? $user->getDirectPermissions()->pluck('name')->values()->all() : [];
    }

    private function assertLastSuperAdmin(Model $user): void
    {
        $super = (string) config('nuewire.acl.super_admin_role', 'super-admin');

        if (! method_exists($user, 'hasRole') || ! $user->hasRole($super) || in_array($super, $this->selectedRoles, true)) {
            return;
        }

        $remaining = $user->newQuery()
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', static fn ($query) => $query->where('name', $super))
            ->exists();

        if (! $remaining) {
            throw ValidationException::withMessages(['selectedRoles' => $this->t('validation.last_super_admin')]);
        }
    }

    private function resolveLocale(?string $locale): string
    {
        $supported = (array) config('nuewire.acl.supported_locales', ['id', 'en']);
        $locale = $locale ?: (string) config('nuewire.acl.locale', 'id');

        return in_array($locale, $supported, true) ? $locale : 'id';
    }

    private function t(string $key): string
    {
        return __("nuewire-acl::acl.{$key}", [], $this->locale);
    }
}
