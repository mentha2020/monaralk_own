<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\MakeResource\Pages;
use App\Modules\Catalog\Models\Make;
use Filament\Forms;

class MakeResource extends TaxonomyResource
{
    protected static ?string $model = Make::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'make';

    protected static ?string $pluralModelLabel = 'makes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('country')
                ->label('Country of origin')
                ->maxLength(80),

            Forms\Components\FileUpload::make('logo_path')
                ->label('Logo')
                ->directory('makes')
                ->disk('public')
                ->image()
                ->maxSize(2048),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMakes::route('/'),
            'create' => Pages\CreateMake::route('/create'),
            'edit' => Pages\EditMake::route('/{record}/edit'),
        ];
    }
}
