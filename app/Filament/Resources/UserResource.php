<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\District;
use App\Models\Federation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Utilisateur';
    protected static ?string $pluralModelLabel = 'Utilisateurs';
    protected static ?string $navigationLabel = 'Utilisateurs';

    protected const ROLES_INTERDITS_FEDERATION_ADMIN = ['admin', 'super_admin', 'federation_admin'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                forms\Components\Section::make('Informations du compte')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\DateTimePicker::make('email_verified_at'),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->helperText('Laisser vide pour ne pas changer le mot de passe existant.')
                            ->minLength(8),
                    ])
                    ->columns(2),
                

                Forms\Components\Section::make('Rôle')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Rôle')
                            ->relationship('roles', 'name')
                            ->options(fn () => static::assignableRoles())
                            ->rules(fn () => [Rule::in(static::assignableRoles()->keys()->all())])
                            ->preload()
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record?->roles?->first()?->id);
                            }),
                        Forms\Components\Select::make('federation_id')
                            ->label('Fédération')
                            ->relationship('federation', 'name')
                            ->preload()
                            ->searchable()
                            ->required(fn (Get $get) => static::roleNeedsScope($get('roles')[0] ?? null))
                            ->visible(fn (Get $get) => static::roleNeedsScope($get('roles')[0] ?? null))
                            ->default(function () {
                                /** @var User $user */
                                $user = Auth::user();

                                return $user->hasRole('federation_admin') ? (string) $user->federation_id : null;
                            })
                            ->disabled(function () {
                                /** @var User $user */
                                $user = Auth::user();

                                return $user->hasRole('federation_admin')
                                    && ! $user->hasRole(['admin', 'super_admin']);
                            })
                            ->live()
                            ->dehydrated(),
                        Forms\Components\Select::make('district_id')
                            ->label('District')
                            ->relationship(
                                name: 'district',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, Get $get) => $query->when(
                                    $get('federation_id'),
                                    fn ($q) => $q->where('federation_id', $get('federation_id'))
                                ),
                            )
                            ->preload()
                            ->searchable()
                            ->required(fn (Get $get) => static::roleNeedsScope($get('roles')[0] ?? null, onlyDistrict: true))
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $set('federation_id', District::find($state)?->federation_id);
                            })
                            ->visible(fn (Get $get) => static::roleNeedsScope($get('roles')[0] ?? null, onlyDistrict: true))
                            ->default(null),
                    ]),
            ])
            ->columns(2);
    }


    protected static function roleNeedsScope(int|string|null $roleId, bool $onlyDistrict = false)
    {
        if (empty($roleId)) return false;

        // Récupère directement le nom du rôle unique par son ID
        $roleName = \Spatie\Permission\Models\Role::where('id', $roleId)->value('name');

        if ($roleName === 'admin') {
            return false;
        }
            
        else if ($onlyDistrict) {
            return $roleName === 'district_manager';
        }

        return in_array($roleName, ['district_manager', 'federation_admin']);
    }

    public static function assignableRoles(): Collection
        {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $query = Role::query();

            // L'administrateur garde tous les rôles ; l'administrateur de fédération est limité
            if (! $user->hasRole(['admin', 'super_admin']) && $user->hasRole('federation_admin')) {
                $query->whereNotIn('name', self::ROLES_INTERDITS_FEDERATION_ADMIN);
            }

            return $query->pluck('name', 'id');
        }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('federation.name')
                    ->label('Fédération')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
        {
            $query = parent::getEloquentQuery();

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user->hasRole(['admin', 'super_admin']) && $user->hasRole('federation_admin')) {
                $query->where('federation_id', $user->federation_id)
                    ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::ROLES_INTERDITS_FEDERATION_ADMIN));
            }

            return $query;
        }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
