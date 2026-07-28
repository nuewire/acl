<?php

declare(strict_types=1);

namespace Nuewire\Acl\Tests;

use Livewire\Livewire;

final class ComponentRegistrationTest extends TestCase
{
    public function test_acl_components_are_registered(): void
    {
        Livewire::test('nuewire::acl')->assertStatus(403);
        Livewire::test('nuewire::user-access', ['userId' => '1'])->assertStatus(403);
    }
}
