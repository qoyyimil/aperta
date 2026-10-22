<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Photographer = 'photographer';
    case Editor = 'editor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Photographer => 'Fotografer',
            self::Editor => 'Editor',
        };
    }
}
