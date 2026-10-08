<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Filament\Resources\FeatureResource\Pages;
use App\Modules\Catalog\Models\Feature;
use Filament\Forms;
use Filament\Tables;

class FeatureResource extends TaxonomyResource
{
    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 16;

    protected static ?string $modelLabel = 'feature';

    protected static ?string $pluralModelLabel = 'features';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('icon')
                ->label('Icon')
                ->maxLength(60)
                ->helperText('Optional heroicon name, e.g. heroicon-o-camera'),

            Forms\Components\TextInput::make('group_name')
                ->label('Group')
                ->maxLength(60)
                ->helperText('Used to cluster features on the detail page.'),
        ];
    }

    public static function extraColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('group_name')
                ->label('Group')
                ->badge()
                ->color('gray')
                ->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit' => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
