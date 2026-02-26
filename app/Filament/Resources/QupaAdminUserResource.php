<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QupaAdminUserResource\Pages;
use App\Models\Branch;
use App\Models\QupaReferralLink;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class QupaAdminUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Qupa Admin Users';

    protected static ?string $navigationGroup = 'Qupa Admin';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Qupa Admin User';

    protected static ?string $pluralModelLabel = 'Qupa Admin Users';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', User::ROLE_QUPA_ADMIN);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('e.g. +263771234567'),
                        Forms\Components\Select::make('designation')
                            ->options([
                                User::DESIGNATION_LOAN_OFFICER => 'Loan Officer',
                                User::DESIGNATION_BRANCH_MANAGER => 'Branch Manager',
                                User::DESIGNATION_VLC => 'VLC',
                                User::DESIGNATION_QUPA_MANAGEMENT => 'Qupa Management',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::active()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) =>
                                !in_array($get('designation'), [User::DESIGNATION_VLC, User::DESIGNATION_QUPA_MANAGEMENT])
                            )
                            ->required(fn (Forms\Get $get) =>
                                in_array($get('designation'), [User::DESIGNATION_LOAN_OFFICER, User::DESIGNATION_BRANCH_MANAGER])
                            ),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('designation')
                    ->label('Designation')
                    ->colors([
                        'info' => User::DESIGNATION_LOAN_OFFICER,
                        'success' => User::DESIGNATION_BRANCH_MANAGER,
                        'warning' => User::DESIGNATION_VLC,
                        'primary' => User::DESIGNATION_QUPA_MANAGEMENT,
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        User::DESIGNATION_LOAN_OFFICER => 'Loan Officer',
                        User::DESIGNATION_BRANCH_MANAGER => 'Branch Manager',
                        User::DESIGNATION_VLC => 'VLC',
                        User::DESIGNATION_QUPA_MANAGEMENT => 'Qupa Management',
                        default => $state ?? 'N/A',
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->default('All Branches')
                    ->sortable(),
                Tables\Columns\TextColumn::make('referral_link')
                    ->label('Referral Link')
                    ->getStateUsing(function (Model $record) {
                        $link = $record->qupaReferralLinks()->where('is_active', true)->first();
                        return $link ? $link->url : '—';
                    })
                    ->copyable()
                    ->copyMessage('Referral link copied!')
                    ->limit(40),
                Tables\Columns\TextColumn::make('qupaApplications_count')
                    ->label('Applications')
                    ->counts('qupaApplications')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('generate_referral_link')
                    ->label('Generate Link')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Referral Link')
                    ->modalDescription('This will create a new unique referral link for this officer. Clients who apply via this link will be routed to their branch.')
                    ->action(function (Model $record) {
                        $link = QupaReferralLink::generateForUser($record);
                        Notification::make()
                            ->title('Referral Link Generated')
                            ->body("Link: {$link->url}")
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn (Model $record) =>
                        in_array($record->designation, [User::DESIGNATION_LOAN_OFFICER, User::DESIGNATION_BRANCH_MANAGER])
                    ),

                Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Model $record) {
                        $newPassword = Str::random(12);
                        $record->update(['password' => Hash::make($newPassword)]);

                        // Email the new password
                        try {
                            Mail::raw(
                                "Hello {$record->name},\n\nYour Qupa Admin password has been reset.\n\nEmail: {$record->email}\nNew Password: {$newPassword}\n\nPlease log in at " . url('/zb-admin') . " and change your password.\n\nRegards,\nBancoSystem Admin",
                                function ($message) use ($record) {
                                    $message->to($record->email)
                                        ->subject('Qupa Admin — Password Reset');
                                }
                            );
                            Notification::make()
                                ->title('Password Reset')
                                ->body("New password: {$newPassword}\nAn email has been sent to {$record->email}")
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Password Reset (Email Failed)')
                                ->body("New password: {$newPassword}\nEmail could not be sent: {$e->getMessage()}")
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQupaAdminUsers::route('/'),
            'create' => Pages\CreateQupaAdminUser::route('/create'),
            'edit' => Pages\EditQupaAdminUser::route('/{record}/edit'),
        ];
    }
}
