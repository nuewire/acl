<?php

declare(strict_types=1);

namespace Nuewire\Acl\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class AclAuthorizer
{
    public function __construct(private readonly AclState $state)
    {
    }

    public function authorize(string $ability): Authenticatable
    {
        $guard = config('nuewire.acl.authorization.guard');
        $actor = is_string($guard) && $guard !== ''
            ? Auth::guard($guard)->user()
            : Auth::user();

        if (! $actor instanceof Authenticatable) {
            abort_if((bool) config('nuewire.acl.authorization.require_authenticated_user', true), 403);
            abort(403);
        }

        $gate = trim((string) config('nuewire.acl.authorization.gate', ''));

        if ($gate !== '' && ! Gate::forUser($actor)->allows($gate)) {
            abort(403);
        }

        if ($this->state->simpleAdminFallback($actor)) {
            return $actor;
        }

        if (! $this->state->ready()) {
            abort(503, 'Nuewire ACL has not been initialized.');
        }

        try {
            if (Gate::forUser($actor)->allows($ability)) {
                return $actor;
            }
        } catch (Throwable) {
            abort(403);
        }

        abort(403);
    }
}
