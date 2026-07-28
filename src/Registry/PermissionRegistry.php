<?php

declare(strict_types=1);

namespace Nuewire\Acl\Registry;

final class PermissionRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $permissions = [];

    /** @param array<string, mixed>|string $label */
    public function register(string $name, array|string $label, string $group = 'general'): self
    {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/', $name)) {
            throw new \InvalidArgumentException('Permission names may contain lowercase letters, numbers, dots, underscores, and hyphens.');
        }

        $this->permissions[$name] = [
            'name' => $name,
            'label' => $label,
            'group' => $group,
        ];

        return $this;
    }

    /** @param array<string, array<string, mixed>|string> $permissions */
    public function registerMany(array $permissions, string $group = 'general'): self
    {
        foreach ($permissions as $name => $label) {
            $this->register((string) $name, $label, $group);
        }

        return $this;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(string $locale = 'id'): array
    {
        $result = [];

        foreach ($this->permissions as $name => $permission) {
            $label = $permission['label'];
            $result[$name] = [
                'name' => $name,
                'label' => is_array($label)
                    ? (string) ($label[$locale] ?? $label['id'] ?? $label['en'] ?? reset($label) ?: $name)
                    : (string) $label,
                'group' => (string) $permission['group'],
            ];
        }

        uasort($result, static fn (array $a, array $b): int => [$a['group'], $a['label']] <=> [$b['group'], $b['label']]);

        return $result;
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->permissions);
    }
}
