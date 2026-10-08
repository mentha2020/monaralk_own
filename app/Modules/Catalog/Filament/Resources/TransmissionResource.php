<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\TransmissionResource\Pages;
use App\Modules\Catalog\Models\Transmission;
use Filament\Forms;

class TransmissionResource extends TaxonomyResource
{
    protected static ?string $model = Transmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'transmission';

    protected static ?string $pluralModelLabel = 'transmissions';

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
            'index' => Pages\ListTransmissions::route('/'),
            'create' => Pages\CreateTransmission::route('/create'),
            'edit' => Pages\EditTransmission::route('/{record}/edit'),
        ];
    }
}
