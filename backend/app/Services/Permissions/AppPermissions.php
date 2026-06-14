<?php

namespace App\Services\Permissions;

use App\Enums\AppPermission;

class AppPermissions
{
    private int $permissions;

    public function __construct(int $permissions = 0)
    {
        $this->permissions = $permissions;
    }

    public function add(AppPermission $permissionFlag): self
    {
        $this->permissions |= $permissionFlag->value;

        return $this;
    }

    public function remove(AppPermission $permissionFlag): self
    {
        $this->permissions &= ~$permissionFlag->value;

        return $this;
    }

    public function merge(int $value): self
    {
        $this->permissions |= $value;

        return $this;
    }

    public function discard(int $value): self
    {
        $this->permissions &= ~$value;

        return $this;
    }

    public function can(AppPermission $permissionFlag): bool
    {
        return ($this->permissions & $permissionFlag->value) === $permissionFlag->value;
    }

    public function mask(int $value): self
    {
        $this->permissions &= $value;

        return $this;
    }

    public function value(): int
    {
        return $this->permissions;
    }
}
