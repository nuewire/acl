@php $t = static fn (string $key): string => __("nuewire-acl::acl.{$key}", [], $locale); @endphp
<div class="nwuacl">
    <style>
        .nwuacl{display:grid;gap:13px}.nwuacl-box{border:1px solid #e4e7ec;border-radius:10px;padding:12px}.nwuacl-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;max-height:230px;overflow:auto}.nwuacl-actions{display:flex;align-items:center;gap:9px}.nwuacl-button{border:1px solid #e4e7ec;border-radius:9px;background:#172033;color:#fff;padding:8px 11px;cursor:pointer}.nwuacl-status{padding:9px 11px;border-radius:9px;background:#ecfdf3;color:#067647}.nwuacl-muted{color:#667085}.nwuacl-badges{display:flex;flex-wrap:wrap;gap:6px}.nwuacl-badge{border:1px solid #e4e7ec;border-radius:999px;padding:3px 7px;font-size:12px}@media(max-width:640px){.nwuacl-grid{grid-template-columns:1fr}}
    </style>
    @if($status !== '')<div class="nwuacl-status">{{ $status }}</div>@endif
    @error('access')<div class="nwuacl-status" style="background:#fef3f2;color:#b42318">{{ $message }}</div>@enderror
    @error('selectedRoles')<div class="nwuacl-status" style="background:#fef3f2;color:#b42318">{{ $message }}</div>@enderror

    <section class="nwuacl-box">
        <strong>{{ $t('roles') }}</strong>
        <div class="nwuacl-grid" style="margin-top:9px">
            @foreach($roles as $role)<label><input type="checkbox" value="{{ $role->name }}" wire:model="selectedRoles"> {{ $role->name }}</label>@endforeach
        </div>
    </section>
    <section class="nwuacl-box">
        <strong>{{ $t('direct_permissions') }}</strong>
        <div class="nwuacl-grid" style="margin-top:9px">
            @foreach($permissions as $permission)<label><input type="checkbox" value="{{ $permission->name }}" wire:model="selectedPermissions"> {{ $permission->name }}</label>@endforeach
        </div>
    </section>
    <section class="nwuacl-box">
        <strong>{{ $t('effective_permissions') }}</strong>
        <div class="nwuacl-badges" style="margin-top:9px">@forelse($effectivePermissions as $permission)<span class="nwuacl-badge">{{ $permission }}</span>@empty<span class="nwuacl-muted">{{ $t('none') }}</span>@endforelse</div>
    </section>
    <div class="nwuacl-actions"><button class="nwuacl-button" wire:click="save">{{ $t('save_access') }}</button></div>
</div>
