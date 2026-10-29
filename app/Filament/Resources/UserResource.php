<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\District;
use App\Models\User;
use App\Support\Perimeter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    // Rôles qu'un administrateur de fédération ne peut pas attribuer
    protected const ROLES_INTERDITS_FEDERATION_ADMIN = ['admin', 'super_admin', 'federation_admin'];

    // Rôles qui n'ont jamais de périmètre (ils voient tout)
    protected const ROLES_SANS_PERIMETRE = ['admin', 'super_admin'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du compte')
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

                Forms\Components\Section::make('Rôle et périmètre')
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
                            ->relationship('federation', 'name', modifyQueryUsing: fn (Builder $query) => Perimeter::federations($query))
                            ->preload()
                            ->searchable()
                            ->live()
                            ->default(fn () => static::isRestrictedFederationAdmin() ? Auth::user()->federation_id : null)
                            ->disabled(fn () => static::isRestrictedFederationAdmin())
                            ->dehydrated()
                            ->visible(fn (Get $get) => static::hasScope($get('roles')))
                            // Obligatoire seulement pour ces deux rôles (supprimer la ligne pour tout rendre facultatif)
                            ->required(fn (Get $get) => in_array(static::roleName($get('roles')), ['federation_admin', 'district_manager'], true))
                            ->helperText("Sans fédération ni district, l'utilisateur voit toutes les données (dans la limite de ses droits)."),

                        Forms\Components\Select::make('district_id')
                            ->label('District')
                            ->relationship(
                                name: 'district',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => Perimeter::districts($query)
                                    ->when($get('federation_id'), fn ($q) => $q->where('federation_id', $get('federation_id'))),
                            )
                            ->preload()
                            ->searchable()
                            ->live()
                            ->visible(fn (Get $get) => static::hasScope($get('roles'))
                                && static::roleName($get('roles')) !== 'federation_admin')
                            // Obligatoire seulement pour le responsable de district
                            ->required(fn (Get $get) => static::roleName($get('roles')) === 'district_manager')
                            ->rules(fn () => static::isRestrictedFederationAdmin()
                                ? [Rule::exists('districts', 'id')->where('federation_id', Auth::user()->federation_id)]
                                : [])
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $set('federation_id', District::find($state)?->federation_id);
                                }
                            })
                            ->helperText("Facultatif. Si renseigné, l'accès est limité à ce district ; sinon, à toute la fédération."),
                    ])
                    ->columns(2),
            ]);
    }

    // ---- Aides ---------------------------------------------------------

    protected static function roleName(mixed $state): ?string
    {
        $id = is_array($state) ? ($state[0] ?? null) : $state;

        return $id ? Role::whereKey($id)->value('name') : null;
    }

    // Les champs fédération / district s'affichent pour tout rôle sauf admin et super_admin
    protected static function hasScope(mixed $state): bool
    {
        $name = static::roleName($state);

        return $name !== null && ! in_array($name, self::ROLES_SANS_PERIMETRE, true);
    }

    protected static function isRestrictedFederationAdmin(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->hasRole('federation_admin') && ! $user->hasRole(['admin', 'super_admin']);
    }

    public static function assignableRoles(): Collection
    {
        $query = Role::query();

        if (static::isRestrictedFederationAdmin()) {
            $query->whereNotIn('name', self::ROLES_INTERDITS_FEDERATION_ADMIN);
        }

        return $query->pluck('name', 'id');
    }

    // Rend le périmètre cohérent après chaque création / modification
    public static function normalizeScope(User $record): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $role = $record->roles()->first()?->name;

        if (in_array($role, self::ROLES_SANS_PERIMETRE, true)) {
            $record->federation_id = null;
            $record->district_id = null;
        } else {
            if ($role === 'federation_admin') {
                $record->district_id = null;
            }
            // Le district détermine la fédération
            if ($record->district_id) {
                $record->federation_id = District::find($record->district_id)?->federation_id;
            }
        }

        // Un administrateur de fédération n'agit que dans sa fédération
        if (static::isRestrictedFederationAdmin()) {
            $record->federation_id = $actor->federation_id;

            if ($record->district_id
                && District::find($record->district_id)?->federation_id !== $actor->federation_id) {
                $record->district_id = null;
            }
        }

        $record->save();
    }

    // ---- Tableau -------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Rôle')->badge(),
                Tables\Columns\TextColumn::make('federation.name')->label('Fédération')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('district.name')->label('District')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

        if (static::isRestrictedFederationAdmin()) {
            $query->where('federation_id', Auth::user()->federation_id)
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', self::ROLES_INTERDITS_FEDERATION_ADMIN));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
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