<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Coolsam\Modules\ModulesPlugin;
use Filament\Forms\Components\FileUpload;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Modules\Sandbox\Models\SandboxModel;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            // ->databaseNotifications()
            // ->profile()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                    // StatWidget::class,
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Sandbox')
                    ->icon('heroicon-s-cube')
                    ->url('/')
                    ->sort(1)
                    ->openUrlInNewTab(true)
            ])
            ->collapsibleNavigationGroups(true)
            ->plugins([
                ModulesPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        userMenuLabel: 'โปรไฟล์ของฉัน',
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                    )
                    ->avatarUploadComponent(
                        fn()
                        => FileUpload::make('avatar_url')
                            ->label('Avatar')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->deleteUploadedFileUsing(function ($file) {
                                Storage::disk('public')->delete($file);
                            }),
                    )
                    ->enableTwoFactorAuthentication()
            ]);
    }
}
