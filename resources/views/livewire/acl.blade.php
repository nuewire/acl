@php $t = static fn (string $key): string => __("nuewire-acl::acl.{$key}", [], $locale); @endphp
<div class="nwacl">
    <style>
        .nwacl{--a-b:#e4e7ec;--a-bg:#fff;--a-soft:#f7f8fa;--a-text:#182230;--a-muted:#667085;color:var(--a-text);font:14px/1.45 ui-sans-serif,system-ui,-apple-system,sans-serif}.nwacl *{box-sizing:border-box}.nwa-head,.nwa-row,.nwa-actions,.nwa-toolbar{display:flex;align-items:center;gap:10px}.nwa-head,.nwa-toolbar{justify-content:space-between}.nwa-head{margin-bottom:16px}.nwa-title{margin:0;font-size:24px}.nwa-muted{color:var(--a-muted)}.nwa-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.nwa-card{background:var(--a-bg);border:1px solid var(--a-b);border-radius:14px;padding:16px}.nwa-input{width:100%;border:1px solid var(--a-b);border-radius:9px;padding:9px 11px;background:var(--a-bg);color:var(--a-text)}.nwa-button{border:1px solid var(--a-b);border-radius:9px;background:var(--a-bg);color:var(--a-text);padding:8px 11px;cursor:pointer}.nwa-button.primary{background:#172033;color:#fff;border-color:#172033}.nwa-button.danger{color:#b42318}.nwa-list{display:grid;gap:8px;margin-top:12px}.nwa-item{border:1px solid var(--a-b);border-radius:10px;padding:11px}.nwa-grow{flex:1}.nwa-badge{border:1px solid var(--a-b);border-radius:999px;padding:3px 7px;font-size:12px}.nwa-form{display:grid;gap:10px;margin-top:12px;padding:12px;background:var(--a-soft);border-radius:10px}.nwa-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;max-height:270px;overflow:auto}.nwa-status{margin-bottom:12px;padding:10px 12px;border-radius:9px;background:#ecfdf3;color:#067647}.nwa-status.error{background:#fef3f2;color:#b42318}.nwa-error{color:#b42318;font-size:12px}.nwa-section-title{margin:0;font-size:17px}@media(max-width:850px){.nwa-grid{grid-template-columns:1fr}.nwa-checks{grid-template-columns:1fr}}
    </style>

    <div class="nwa-head">
        <div><h2 class="nwa-title">{{ $t('title') }}</h2><div class="nwa-muted">{{ $t('subtitle') }}</div></div>
        @if($ready)<button class="nwa-button" wire:click="syncRegistry">{{ $t('sync') }}</button>@endif
    </div>

    @if(!$ready)
        <div class="nwa-card">
            <strong>{{ $t('setup_required') }}</strong>
            <div class="nwa-muted" style="margin-top:6px">{{ $t('setup_help') }}</div>
            <code style="display:block;margin-top:10px">php artisan nuewire:acl:install --migrate --user=admin@example.com</code>
        </div>
    @else
    @if($status !== '')<div class="nwa-status {{ $statusType === 'error' ? 'error' : '' }}">{{ $status }}</div>@endif
    @error('role')<div class="nwa-status error">{{ $message }}</div>@enderror
    @error('permission')<div class="nwa-status error">{{ $message }}</div>@enderror

    <div class="nwa-grid">
        <section class="nwa-card">
            <div class="nwa-toolbar"><h3 class="nwa-section-title">{{ $t('roles') }}</h3><button class="nwa-button primary" wire:click="createRole">{{ $t('add_role') }}</button></div>
            <input class="nwa-input" type="search" wire:model.live.debounce.300ms="roleSearch" placeholder="{{ $t('search_roles') }}">

            @if($editingRoleId !== '')
                <form class="nwa-form" wire:submit="saveRole">
                    <label>{{ $t('role_name') }}<input class="nwa-input" wire:model="roleName"></label>
                    @error('roleName')<div class="nwa-error">{{ $message }}</div>@enderror
                    <strong>{{ $t('permissions') }}</strong>
                    <div class="nwa-checks">
                        @foreach($permissions as $permission)
                            <label><input type="checkbox" value="{{ $permission->name }}" wire:model="rolePermissions"> {{ $permission->name }}</label>
                        @endforeach
                    </div>
                    @error('rolePermissions.*')<div class="nwa-error">{{ $message }}</div>@enderror
                    <div class="nwa-actions"><button class="nwa-button primary" type="submit">{{ $t('save') }}</button><button class="nwa-button" type="button" wire:click="cancelRole">{{ $t('cancel') }}</button></div>
                </form>
            @endif

            <div class="nwa-list">
                @forelse($roles as $role)
                    <div class="nwa-item" wire:key="role-{{ $role->getKey() }}">
                        <div class="nwa-row"><strong class="nwa-grow">{{ $role->name }}</strong><span class="nwa-badge">{{ $role->permissions_count }} {{ $t('permissions') }}</span><span class="nwa-badge">{{ $role->users_count }} {{ $t('users') }}</span></div>
                        <div class="nwa-actions" style="margin-top:9px"><button class="nwa-button" wire:click="editRole('{{ $role->getKey() }}')">{{ $t('edit') }}</button><button class="nwa-button danger" wire:click="deleteRole('{{ $role->getKey() }}')" wire:confirm="{{ $t('delete_role_confirm') }}">{{ $t('delete') }}</button></div>
                    </div>
                @empty<div class="nwa-muted">{{ $t('no_roles') }}</div>@endforelse
            </div>
        </section>

        <section class="nwa-card">
            <h3 class="nwa-section-title">{{ $t('permissions') }}</h3>
            <form class="nwa-row" style="margin:12px 0" wire:submit="createPermission"><input class="nwa-input" wire:model="permissionName" placeholder="users.export"><button class="nwa-button primary" type="submit">{{ $t('add') }}</button></form>
            @error('permissionName')<div class="nwa-error">{{ $message }}</div>@enderror
            <input class="nwa-input" type="search" wire:model.live.debounce.300ms="permissionSearch" placeholder="{{ $t('search_permissions') }}">
            <div class="nwa-list">
                @forelse($permissions as $permission)
                    @php $meta = $registered[$permission->name] ?? null; @endphp
                    <div class="nwa-item" wire:key="permission-{{ $permission->getKey() }}">
                        <div class="nwa-row"><div class="nwa-grow"><strong>{{ $permission->name }}</strong>@if($meta)<div class="nwa-muted">{{ $meta['label'] }}</div>@endif</div><span class="nwa-badge">{{ $permission->roles_count }} {{ $t('roles') }}</span>@if(!$meta)<button class="nwa-button danger" wire:click="deletePermission('{{ $permission->getKey() }}')" wire:confirm="{{ $t('delete_permission_confirm') }}">{{ $t('delete') }}</button>@endif</div>
                    </div>
                @empty<div class="nwa-muted">{{ $t('no_permissions') }}</div>@endforelse
            </div>
        </section>
    </div>
    @endif
</div>
