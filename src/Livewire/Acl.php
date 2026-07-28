<?php

declare(strict_types=1);

namespace Nuewire\Acl\Livewire;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Nuewire\Acl\Registry\PermissionRegistry;
use Nuewire\Acl\Support\AclAuthorizer;
use Nuewire\Acl\Support\AclState;
use Nuewire\Acl\Support\SpatieModels;
use Spatie\Permission\PermissionRegistrar;

final class Acl extends Component
{
    public string $locale = 'id';
    public string $roleSearch = '';
    public string $permissionSearch = '';
    public string $editingRoleId = '';
    public string $roleName = '';

    /** @var array<int, string> */
    public array $rolePermissions = [];

    public string $permissionName = '';
    public string $status = '';
    public string $statusType = 'success';

    public function mount(?string $locale = null): void
    {
        app(AclAuthorizer::class)->authorize('acl.view');
        $this->locale = $this->resolveLocale($locale);
    }

    public function createRole(): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $this->resetRoleForm();
        $this->editingRoleId = 'new';
    }

    public function editRole(string|int $id, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $role = $spatie->roles()->findOrFail($id);
        $this->editingRoleId = (string) $role->getKey();
        $this->roleName = (string) $role->name;
        $this->rolePermissions = $role->permissions()->pluck('name')->values()->all();
        $this->resetValidation();
    }

    public function cancelRole(): void
    {
        $this->resetRoleForm();
    }

    public function saveRole(PermissionRegistrar $registrar, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $guard = $this->guard();
        $id = $this->editingRoleId === 'new' ? null : $this->editingRoleId;

        $this->validate([
            'roleName' => [
                'required', 'string', 'max:125', 'regex:/^[A-Za-z0-9._ -]+$/',
                Rule::unique($spatie->roleClass(), 'name')->where('guard_name', $guard)->ignore($id),
            ],
            'rolePermissions' => ['array'],
            'rolePermissions.*' => ['string', Rule::exists($spatie->permissionClass(), 'name')->where('guard_name', $guard)],
        ]);

        $role = $id ? $spatie->roles()->findOrFail($id) : $spatie->newRole();
        $newName = trim($this->roleName);
        $protectedName = (string) config('nuewire.acl.super_admin_role', 'super-admin');

        if ($id !== null && (string) $role->name === $protectedName && $newName !== $protectedName) {
            throw ValidationException::withMessages(['roleName' => $this->t('validation.protected_role')]);
        }

        $role->name = $newName;
        $role->guard_name = $guard;
        $role->save();
        $role->syncPermissions($this->rolePermissions);
        $registrar->forgetCachedPermissions();

        $this->resetRoleForm();
        $this->setStatus('status.role_saved');
    }

    public function deleteRole(string|int $id, PermissionRegistrar $registrar, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $role = $spatie->roles()->findOrFail($id);

        if ((string) $role->name === (string) config('nuewire.acl.super_admin_role', 'super-admin')) {
            throw ValidationException::withMessages(['role' => $this->t('validation.protected_role')]);
        }

        $role->delete();
        $registrar->forgetCachedPermissions();
        $this->setStatus('status.role_deleted');
    }

    public function createPermission(PermissionRegistrar $registrar, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $guard = $this->guard();
        $this->validate([
            'permissionName' => [
                'required', 'string', 'max:125', 'regex:/^[a-z0-9][a-z0-9._-]*$/',
                Rule::unique($spatie->permissionClass(), 'name')->where('guard_name', $guard),
            ],
        ]);

        $spatie->findOrCreatePermission(trim($this->permissionName), $guard);
        $registrar->forgetCachedPermissions();
        $this->permissionName = '';
        $this->setStatus('status.permission_saved');
    }

    public function deletePermission(string|int $id, PermissionRegistry $registry, PermissionRegistrar $registrar, SpatieModels $spatie): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $permission = $spatie->permissions()->findOrFail($id);

        if (in_array((string) $permission->name, $registry->names(), true)) {
            throw ValidationException::withMessages(['permission' => $this->t('validation.registered_permission')]);
        }

        $permission->delete();
        $registrar->forgetCachedPermissions();
        $this->setStatus('status.permission_deleted');
    }

    public function syncRegistry(): void
    {
        app(AclAuthorizer::class)->authorize('acl.manage');
        $this->ensureReady();
        $this->dispatch('nuewire-acl-sync-requested');
        $exit = \Illuminate\Support\Facades\Artisan::call('nuewire:acl:sync');
        $this->setStatus($exit === 0 ? 'status.synced' : 'status.sync_failed', $exit === 0 ? 'success' : 'error');
    }

    public function render(PermissionRegistry $registry, AclState $state, SpatieModels $spatie)
    {
        app(AclAuthorizer::class)->authorize('acl.view');
        $ready = $state->ready();
        $roleSearch = trim($this->roleSearch);
        $permissionSearch = trim($this->permissionSearch);

        if (! $ready) {
            return view('nuewire-acl::livewire.acl', [
                'ready' => false,
                'roles' => collect(),
                'permissions' => collect(),
                'registered' => $registry->all($this->locale),
            ]);
        }

        $roles = $spatie->roles()
            ->where('guard_name', $this->guard())
            ->when($roleSearch !== '', static fn ($query) => $query->where('name', 'like', '%'.$roleSearch.'%'))
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        $permissions = $spatie->permissions()
            ->where('guard_name', $this->guard())
            ->when($permissionSearch !== '', static fn ($query) => $query->where('name', 'like', '%'.$permissionSearch.'%'))
            ->withCount('roles')
            ->orderBy('name')
            ->get();

        return view('nuewire-acl::livewire.acl', [
            'roles' => $roles,
            'permissions' => $permissions,
            'registered' => $registry->all($this->locale),
            'ready' => true,
        ]);
    }

    private function ensureReady(): void
    {
        abort_unless(app(AclState::class)->ready(), 503, 'Nuewire ACL has not been initialized.');
    }

    private function resetRoleForm(): void
    {
        $this->editingRoleId = '';
        $this->roleName = '';
        $this->rolePermissions = [];
        $this->resetValidation();
    }

    private function guard(): string
    {
        return (string) config('nuewire.acl.guard_name', 'web');
    }

    private function resolveLocale(?string $locale): string
    {
        $supported = (array) config('nuewire.acl.supported_locales', ['id', 'en']);
        $locale = $locale ?: (string) config('nuewire.acl.locale', 'id');

        return in_array($locale, $supported, true) ? $locale : 'id';
    }

    /** @param array<string, string|int> $replace */
    private function t(string $key, array $replace = []): string
    {
        return __("nuewire-acl::acl.{$key}", $replace, $this->locale);
    }

    private function setStatus(string $key, string $type = 'success'): void
    {
        $this->status = $this->t($key);
        $this->statusType = $type;
    }
}
