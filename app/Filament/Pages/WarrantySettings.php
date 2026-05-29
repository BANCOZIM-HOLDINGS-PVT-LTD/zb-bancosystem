<?php

namespace App\Filament\Pages;

use App\Models\WarrantySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WarrantySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Warranty Settings';
    protected static ?string $title           = 'Warranty Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.warranty-settings';

    public bool $warranty_enabled = true;
    public string $warranty_text  = '12 month warranty';

    public function mount(): void
    {
        $settings = WarrantySetting::current();
        $this->warranty_enabled = $settings->warranty_enabled;
        $this->warranty_text    = $settings->warranty_text;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('warranty_enabled')
                    ->label('Enable Warranty Notice')
                    ->helperText('When enabled, the warranty text is shown below package remarks for all qualifying products.')
                    ->onColor('success')
                    ->offColor('danger'),
                Textarea::make('warranty_text')
                    ->label('Warranty Text')
                    ->helperText('This text appears in the product remarks section. Applies to all bancozim, school booster, and microbiz products (excluding regular chicken/broiler/layer packages).')
                    ->rows(3)
                    ->required()
                    ->maxLength(500),
            ])
            ->statePath('');
    }

    public function save(): void
    {
        $settings = WarrantySetting::current();
        $settings->update([
            'warranty_enabled' => $this->warranty_enabled,
            'warranty_text'    => $this->warranty_text,
        ]);

        Notification::make()
            ->title('Warranty settings saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }
}
