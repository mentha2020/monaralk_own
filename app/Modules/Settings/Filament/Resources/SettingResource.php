<?php

namespace App\Modules\Settings\Filament\Resources;

use App\Modules\Settings\Filament\Resources\SettingResource\Pages;
use App\Modules\Settings\Models\Setting;
use App\Modules\Shared\Rules\ContactNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'setting';

    protected static ?string $pluralModelLabel = 'settings';

    protected static ?string $recordTitleAttribute = 'key';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Setting')
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(120)
                        ->helperText('e.g. contact.phone, site.name, finance.default_apr'),

                    Forms\Components\TextInput::make('group')
                        ->label('Group')
                        ->default('general')
                        ->required()
                        ->maxLength(60),

                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            'string' => 'String',
                            'text' => 'Text',
                            'boolean' => 'Boolean',
                            'integer' => 'Integer',
                            'decimal' => 'Decimal',
                            'array' => 'Array (JSON)',
                        ])->default('string')
                        ->required(),

                    Forms\Components\Textarea::make('value')
                        ->label('Value')
                        ->rows(4)
                        ->rules(fn (Forms\Get $get): array => in_array($get('key'), ['contact.phone', 'contact.whatsapp'], true)
                            ? [new ContactNumber]
                            : [])
                        ->helperText(fn (Forms\Get $get): ?string => in_array($get('key'), ['contact.phone', 'contact.whatsapp'], true)
                            ? 'Must contain 8–15 digits (spaces, +, ( ) are ignored).'
                            : null)
                        ->columnSpanFull(),
                ])->columns(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->options(fn (): array => static::getModel()::query()
                        ->distinct()
                        ->pluck('group', 'group')
                        ->all()),
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
            ->defaultSort('group');
    }

    protected static function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);

        Setting::flushCache();

        return $record;
    }

    protected static function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->save();

        Setting::flushCache();

        return $record;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
