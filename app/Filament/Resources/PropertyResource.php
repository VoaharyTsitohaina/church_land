<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Facades\Auth;
use Dotswan\MapPicker\Fields\Map;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('property_type_id')
                    ->label('Property Type')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('church_id')
                    ->label('Church')
                    ->relationship('church', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('region')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('admin_district')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('commune')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('fokontany')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('address')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('area')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('land_title_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('cadastral_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('legal_status')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('acquisition_mode')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('acquisition_date'),
                Forms\Components\TextInput::make('estimated_value')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('current_value')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('observations')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('history')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('created_by')
                    ->default(fn () => Auth::user()->name)
                    ->disabled()
                    ->dehydrated(false),
                SpatieMediaLibraryFileUpload::make('titre_foncier')
                    ->collection('titre_foncier'),
                SpatieMediaLibraryFileUpload::make('plan')
                    ->collection('plan'),
                SpatieMediaLibraryFileUpload::make('acte')
                    ->collection('acte'),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->collection('photos')
                    ->multiple()
                    ->image(),
                SpatieMediaLibraryFileUpload::make('autres')
                    ->collection('autres')
                    ->multiple(),
                Map::make('location')
                    ->label('Location')
                    ->defaultLocation(latitude: -18.8792, longitude: 47.5079)
                    ->showMarker(true)
                    ->clickable(true)
                    ->zoom(15)
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('latitude', $state['lat']);
                        $set('longitude', $state['lng']);
                    })
                    ->afterStateHydrated(function ($state, $record, Set $set): void {
                        if ($record && $record->latitude && $record->longitude) {
                            $set('location', ['lat' => $record->latitude, 'lng' => $record->longitude]);
                        }
                    })
                    ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('church.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region')
                    ->searchable(),
                Tables\Columns\TextColumn::make('admin_district')
                    ->searchable(),
                Tables\Columns\TextColumn::make('commune')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fokontany')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('land_title_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cadastral_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('legal_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acquisition_mode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acquisition_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('church_id')->relationship('church', 'name'),
                Tables\Filters\SelectFilter::make('property_type_id')->relationship('type', 'name'),
                Tables\Filters\Filter::make('sans_titre')
                ->query(fn ($query) => $query->whereNull('land_title_number')),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

 public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    // Utilisation de la façade Auth pour récupérer l'utilisateur connecté
    $user = Auth::user();
    /** @var \App\Models\User $user */
    
    if ($user->hasRole('district_manager')) {
        $query->whereHas('church', fn ($q) => $q->where('district_id', $user->district_id));
    } elseif ($user->hasRole('federation_admin')) {
        $query->whereHas('church.district', fn ($q) => $q->where('federation_id', $user->federation_id));
    }

    return $query;
}

}
