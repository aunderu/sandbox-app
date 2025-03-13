<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'SUPERADMIN';
    case SCHOOLADMIN = 'SCHOOLADMIN';
    case OFFICER = 'OFFICER';
    case USER = 'USER';

    /**
     * Get the display name for the role.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::SCHOOLADMIN => 'School Admin',
            self::OFFICER => 'Officer',
            self::USER => 'User',
        };
    }

    /**
     * Get all roles as an array for select fields.
     *
     * @return array<string, string>
     */
    public static function getSelectOptions(): array
    {
        return [
            self::SUPERADMIN->value => self::SUPERADMIN->getLabel(),
            self::SCHOOLADMIN->value => self::SCHOOLADMIN->getLabel(),
            self::OFFICER->value => self::OFFICER->getLabel(),
            self::USER->value => self::USER->getLabel(),
        ];
    }

    /**
     * Get the default role.
     *
     * @return self
     */
    public static function getDefault(): self
    {
        return self::USER;
    }
}