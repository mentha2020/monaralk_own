<?php

namespace App\Modules\Catalog\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

abstract class TaxonomyResource extends Resource
{
    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'entry';

    protected static ?string $pluralModelLabel = 'entries';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    ...static::formFields(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort order')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_merge(
                static::leadingColumns(),
                static::extraColumns(),
                [
                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Active')
                        ->boolean(),

                    Tables\Columns\TextColumn::make('sort_order')
                        ->label('Order')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ],
            ))
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    /**
     * @return array<int, Forms\Component>
     */
    abstract public static function formFields(): array;

    /**
     * @return array<int, Tables\Column>
     */
    public static function extraColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, Tables\Column>
     */
    public static function leadingColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->searchable()
                ->toggleable(),
        ];
    }
}
