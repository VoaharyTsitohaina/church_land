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
use App\Exports\ArrayExport;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Override;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Dotswan\MapPicker\Infolists\MapEntry;
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager;

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
                Forms\Components\TextInput::make('current_use')
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
                    ->collection('titre_foncier')
                    ->openable(true),
                SpatieMediaLibraryFileUpload::make('plan')
                    ->openable(true)
                    ->collection('plan'),
                SpatieMediaLibraryFileUpload::make('acte')
                    ->collection('acte')
                    ->openable(true),
                SpatieMediaLibraryFileUpload::make('photos')
                    ->collection('photos')
                    ->multiple()
                    ->panelLayout('grid')
                    ->openable(true)
                    ->responsiveImages()
                    ->image(),
                SpatieMediaLibraryFileUpload::make('autres')
                    ->collection('autres')
                    ->openable(true)
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


    #[Override]
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Identification')
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Reference'),
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('type.name')
                            ->label('Type'),
                    ])->columns(3),

                Section::make('Localisation')
                    ->schema([
                        TextEntry::make('federation.name')
                            ->label('Fédération'),
                        TextEntry::make('district.name')
                            ->label('District'),
                        TextEntry::make('church.name')
                            ->label('Église'),
                        TextEntry::make('region')
                            ->label('Région'),
                        TextEntry::make('admin_district')
                            ->label('District administratif '),
                        TextEntry::make('commune')
                            ->label('Commune'),
                        TextEntry::make('fokontany')
                            ->label('Fokontany'),
                        TextEntry::make('address')
                            ->label('Adresse'),
                        TextEntry::make('latitude')
                            ->label('Latitude'),
                        TextEntry::make('longitude')
                            ->label('Longitude'),
                        MapEntry::make('location')
                            ->label('Localisation')
                            ->default(function ($record) {
                                return [
                                    'lat' => $record->latitude,
                                    'lng' => $record->longitude,
                                ];
                            })
                            ->zoom(15)
                            ->showMarker(true)
                    ])->columns(3),

                Section::make('Information foncière')
                    ->schema([
                        TextEntry::make('area')
                            ->label('Superficie (m²)'),
                        TextEntry::make('land_title_number')
                            ->label('Numéro du titre foncier'),
                        TextEntry::make('cadastral_number')
                            ->label('Numéro cadastral'),
                        TextEntry::make('legal_status')
                            ->label('Statut juridique'),
                        TextEntry::make('acquisition_mode')
                            ->label('Mode d\'acquisition'),
                        TextEntry::make('acquisition_date')
                            ->dateTime()
                            ->label('Date d\'acquisition'),
                        TextEntry::make('estimated_value')
                            ->label('Valeur estimée'),
                    ])->columns(3),

                Section::make('Observations')
                    ->schema([
                        TextEntry::make('current_use')
                            ->label('Utilisation actuelle'),
                        TextEntry::make('observations')
                            ->label('Observations'),
                        TextEntry::make('history')
                            ->label('Historique'),
                    ])->columns(2),

                Section::make('Documents')
                    ->schema([
                        TextEntry::make('titre_foncier')
                            ->label('Titre foncier')
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->getFirstMedia('titre_foncier') ? 'Voir le document' : null)
                            ->placeholder('Aucun document')
                            ->url(fn ($record) => $record->getFirstMedia('titre_foncier')?->getUrl(), shouldOpenInNewTab: true)
                            ->icon('heroicon-o-document'),

                        SpatieMediaLibraryImageEntry::make('plan')
                            ->collection('plan')
                            ->label('Plan')
                            ->size(120)
                            ->placeholder('Aucun document')
                            ->extraImgAttributes(['class' => 'cursor-pointer hover:opacity-80 transition'])
                            ->action(
                                InfolistAction::make('viewPhoto')
                            ->modalContent(fn (array $arguments, $record) => view('filament.infolists.image-preview', [
                                'url' => $arguments['url'] ?? $record->getFirstMediaUrl('plan'),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Fermer')),

                        SpatieMediaLibraryImageEntry::make('acte')
                            ->collection('acte')
                            ->label('Acte')
                            ->size(120)
                            ->placeholder('Aucun document')
                            ->extraImgAttributes(['class' => 'cursor-pointer hover:opacity-80 transition'])
                            ->action(
                                InfolistAction::make('viewPhoto')
                            ->modalContent(fn (array $arguments, $record) => view('filament.infolists.image-preview', [
                                'url' => $arguments['url'] ?? $record->getFirstMediaUrl('acte'),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Fermer')),

                        SpatieMediaLibraryImageEntry::make('photos')
                            ->collection('photos')
                            ->label('Photos')
                            ->size(120)
                            ->placeholder('Aucun document')
                            ->extraImgAttributes(['class' => 'cursor-pointer hover:opacity-80 transition'])
                            ->action(
                                InfolistAction::make('viewPhoto')
                            ->modalContent(fn (array $arguments, $record) => view('filament.infolists.image-preview', [
                                'url' => $arguments['url'] ?? $record->getFirstMediaUrl('photos'),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Fermer')),

                        SpatieMediaLibraryImageEntry::make('autres')
                            ->collection('autres')
                            ->label('Autres')
                            ->size(120)
                            ->placeholder('Aucun document')
                            ->extraImgAttributes(['class' => 'cursor-pointer hover:opacity-80 transition'])
                            ->action(
                                InfolistAction::make('viewPhoto')
                            ->modalContent(fn (array $arguments, $record) => view('filament.infolists.image-preview', [
                                'url' => $arguments['url'] ?? $record->getFirstMediaUrl('autres'),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Fermer')),
                    ])->columns(2),
                            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('church.name')
                    ->label('Église')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('church.district.name')
                    ->label('District')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('church.district.federation.name')
                    ->label('Fédération')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region')
                    ->label('Région')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('admin_district')
                    ->label('District administratif')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commune')
                    ->label('Commune')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fokontany')
                    ->label('Fokontany')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('area')
                    ->label('Superficie (m²)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('land_title_number')
                    ->label('Numéro de titre foncier')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cadastral_number')
                    ->label('Numéro cadastral')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('legal_status')
                    ->label('Statut juridique')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('acquisition_date')
                    ->label('Date d\'acquisition')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Valeur estimée')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_use')
                    ->label('Utilisation actuelle')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('church_id')
                    ->label('Église')
                    ->relationship('church', 'name'),
                Tables\Filters\SelectFilter::make('property_type_id')
                    ->label('Type')
                    ->relationship('type', 'name'),
                Tables\Filters\Filter::make('sans_titre')
                ->query(fn ($query) => $query->whereNull('land_title_number')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            ActivitylogRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'view' => Pages\ViewProperty::route('/{record}'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
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
