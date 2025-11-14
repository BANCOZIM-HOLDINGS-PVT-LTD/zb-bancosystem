<?php

namespace App\Filament\Resources\ApplicationResource\Pages\ViewApplication;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ApplicationStatusWidget extends Widget
{
    protected static string $view = 'filament.resources.application-resource.pages.view-application.widgets.application-status-widget';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';
}
