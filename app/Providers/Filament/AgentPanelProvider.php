<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AgentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('agent')
            ->path('agent')
            ->brandName('BancoSystem Agent Portal')
            ->brandLogo(asset('adala2-removebg-preview.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('adala2-removebg-preview.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                \App\Filament\Agent\Pages\AgentDashboard::class,
            ])
            ->widgets([
                \App\Filament\Agent\Widgets\CommissionBalanceWidget::class,
                \App\Filament\Agent\Widgets\ClientCommissionsWidget::class,
            ])
            ->login(\App\Filament\Agent\Pages\AgentLogin::class)
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\AuthenticateAgent::class,
            ])
            ->authGuard('agent');
    }
}
