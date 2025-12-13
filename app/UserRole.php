<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case PJ = 'pj';
    case Approver = 'approver';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Operator => 'Operator',
            self::PJ => 'Penanggung Jawab',
            self::Approver => 'Approver',
        };
    }
}
