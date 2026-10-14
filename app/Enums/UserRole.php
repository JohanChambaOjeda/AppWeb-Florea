<?php

namespace App\Enums;

enum UserRole: string
{
    use HasValues;

    case Owner = 'owner';
    case Seller = 'seller';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dueño',
            self::Seller => 'Vendedor',
            self::Admin => 'Administrador',
        };
    }
}
