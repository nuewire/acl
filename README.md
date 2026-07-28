# Nuewire ACL

Optional role and permission management for `nuewire/users`, powered by `spatie/laravel-permission`.

## Install

```bash
composer require nuewire/acl
```

Add both Nuewire traits to the application user model:

```php
use Illuminate\Notifications\Notifiable;
use Nuewire\Acl\Concerns\HasNuewireAcl;
use Nuewire\Users\Concerns\NuewireUser;

class User extends Authenticatable
{
    use Notifiable;
    use NuewireUser;
    use HasNuewireAcl;
}
```

Initialize ACL:

```bash
php artisan nuewire:acl:install --migrate --user=admin@example.com
```

Existing `is_admin=true` users are migrated to the `administrator` role. The selected user receives `super-admin`.

## Components

```blade
<livewire:nuewire::users />
<livewire:nuewire::acl />
```

The Users component switches automatically from simple administrator mode to ACL mode after the permission tables and a super admin are ready.

## Commands

```bash
php artisan nuewire:acl:install --migrate --user=admin@example.com
php artisan nuewire:acl:sync
```

Run `nuewire:acl:sync` after installing or updating Nuewire packages so newly registered permissions are created.

## Configuration

```bash
php artisan vendor:publish --tag=nuewire-acl-config
```

```text
config/nuewire/acl.php
```

Complete usage is available in the suite file `docs/USERS_AND_ACL.md`.

## Compatibility notes

Composer selects a compatible Spatie major version for the host Laravel and PHP versions. For UUID or ULID users, customize the published Spatie migration before migrating. The first release uses one global guard and does not provide a teams UI. Role and Permission model classes follow `config/permission.php`.
