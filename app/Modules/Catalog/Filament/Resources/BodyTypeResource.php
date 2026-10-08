<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\BodyTypeResource\Pages;
use App\Modules\Catalog\Models\BodyType;
use Filament\Forms;

class BodyTypeResource extends TaxonomyResource
{
    protected static ?string $model = BodyType::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'body type';

    protected static ?string $pluralModelLabel = 'body types';

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
            'index' => Pages\ListBodyTypes::route('/'),
            'create' => Pages\CreateBodyType::route('/create'),
            'edit' => Pages\EditBodyType::route('/{record}/edit'),
        ];
    }
}
