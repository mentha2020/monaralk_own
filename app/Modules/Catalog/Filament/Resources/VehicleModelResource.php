<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\VehicleModelResource\Pages;
use App\Modules\Catalog\Models\VehicleModel;
use Filament\Forms;
use Filament\Tables;

class VehicleModelResource extends TaxonomyResource
{
    protected static ?string $model = VehicleModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'model';

    protected static ?string $pluralModelLabel = 'models';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formFields(): array
    {
        return [
            Forms\Components\Select::make('make_id')
                ->label('Make')
                ->relationship('make', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(120),
        ];
    }

    public static function leadingColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('make.name')
                ->label('Make')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Model')
                ->searchable()
                ->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleModels::route('/'),
            'create' => Pages\CreateVehicleModel::route('/create'),
            'edit' => Pages\EditVehicleModel::route('/{record}/edit'),
        ];
    }
}
