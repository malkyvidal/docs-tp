<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';

    case Signer = 'signer';

    /**
     * The default role assigned to newly registered users.
     */
    public static function default(): self
    {
        return self::Signer;
    }

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Signer => 'Firmante',
        };
    }
}
