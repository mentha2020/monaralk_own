<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\FuelTypeResource\Pages;
use App\Modules\Catalog\Models\FuelType;
use Filament\Forms;

class FuelTypeResource extends TaxonomyResource
{
    protected static ?string $model = FuelType::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'fuel type';

    protected static ?string $pluralModelLabel = 'fuel types';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(80),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFuelTypes::route('/'),
            'create' => Pages\CreateFuelType::route('/create'),
            'edit' => Pages\EditFuelType::route('/{record}/edit'),
        ];
    }
}
