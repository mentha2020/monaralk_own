<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\ColorResource\Pages;
use App\Modules\Catalog\Models\Color;
use Filament\Forms;
use Filament\Tables;

class ColorResource extends TaxonomyResource
{
    protected static ?string $model = Color::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'colour';

    protected static ?string $pluralModelLabel = 'colours';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(80),

            Forms\Components\ColorPicker::make('hex')
                ->label('Swatch')
                ->hexColor(),
        ];
    }

    public static function extraColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('hex')
                ->label('Swatch')
                ->badge()
                ->color(fn (?string $state): ?string => $state),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListColors::route('/'),
            'create' => Pages\CreateColor::route('/create'),
            'edit' => Pages\EditColor::route('/{record}/edit'),
        ];
    }
}
