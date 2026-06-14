<?php

namespace App\Enums;

enum AppPermission: int
{
    case VIEW_CHANNELS = 1 << 0;
    case SEND_MESSAGES = 1 << 1;
    case ADD_REACTIONS = 1 << 2;
    case ATTACH_FILES = 1 << 3;
    case CREATE_INVITES = 1 << 4;
    case MANAGE_CHANNELS = 1 << 5;
    case MANAGE_MEMBERS = 1 << 6;

    public function label(): string
    {
        return match ($this) {
            self::VIEW_CHANNELS => 'view',
            self::SEND_MESSAGES => 'send',
            self::ADD_REACTIONS => 'react',
            self::ATTACH_FILES => 'attach',
            self::CREATE_INVITES => 'invite',
            self::MANAGE_CHANNELS => 'mod_channels',
            self::MANAGE_MEMBERS => 'mod_members',
        };
    }

    public static function defaultPermissionStack(): array
    {
        return [
            self::VIEW_CHANNELS,
            self::SEND_MESSAGES,
            self::ADD_REACTIONS,
        ];
    }

    public static function channelPermissionStack(): array
    {
        return [
            self::VIEW_CHANNELS,
            self::SEND_MESSAGES,
            self::ADD_REACTIONS,
            self::ATTACH_FILES,
        ];
    }

    public static function basePermissions(): int
    {
        return self::toNumeric(self::defaultPermissionStack());
    }

    public static function allChannelPermissions(): int
    {
        return self::toNumeric(self::channelPermissionStack());
    }

    private static function toNumeric(array $stack): int
    {
        $base = 0;

        foreach ($stack as $permission) {
            $base |= $permission->value;
        }

        return $base;
    }

    public static function all(): int
    {
        $base = 0;

        foreach (self::cases() as $permission) {
            $base |= $permission->value;
        }

        return $base;
    }
}
