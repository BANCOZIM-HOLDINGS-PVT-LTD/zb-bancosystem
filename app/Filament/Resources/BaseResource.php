<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    public static function canCreate(): bool
    {
        if (Filament::getCurrentPanel()->getId() === 'partner') {
            return false;
        }

        return parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        if (Filament::getCurrentPanel()->getId() === 'partner') {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (Filament::getCurrentPanel()->getId() === 'partner') {
            return false;
        }

        return parent::canDelete($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        if (Filament::getCurrentPanel()->getId() === 'partner') {
            return false;
        }

        return parent::canForceDelete($record);
    }

    public static function canReplicate(Model $record): bool
    {
        if (Filament::getCurrentPanel()->getId() === 'partner') {
            return false;
        }

        return parent::canReplicate($record);
    }
}
