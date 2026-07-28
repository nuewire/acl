<?php

declare(strict_types=1);

namespace Nuewire\Acl\Concerns;

use Spatie\Permission\Traits\HasRoles;

trait HasNuewireAcl
{
    use HasRoles;
}
