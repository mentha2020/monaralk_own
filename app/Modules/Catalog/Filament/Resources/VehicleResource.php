<?php

namespace App\Modules\Catalog\Filament\Resources;

use App\Modules\Catalog\Enums\VehicleCondition;
use App\Modules\Catalog\Enums\VehicleStatus;
use App\Modules\Catalog\Filament\Resources\VehicleResource\Pages;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Catalog\Models\VehicleModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'vehicle';

    protected static ?string $pluralModelLabel = 'vehicles';

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?string $navigationBadgeTooltip = 'Published vehicles';

    public static function getNavigationBadge(): ?string
    {
        $badge = static::getModel()::query()->published()->count();

        return $badge > 0 ? (string) $badge : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Vehicle')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Forms\Components\Select::make('make_id')
                                    ->label('Make')
                                    ->relationship('make', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('model_id', null))
                                    ->required(),

                                Forms\Components\Select::make('model_id')
                                    ->label('Model')
                                    ->options(fn (Get $get): array => VehicleModel::query()
                                        ->when($get('make_id'), fn ($query) => $query->where('make_id', $get('make_id')))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->live()
                                    ->required(),

                                Forms\Components\TextInput::make('trim')
                                    ->label('Trim')
                                    ->maxLength(100),

                                Forms\Components\Select::make('condition')
                                    ->label('Condition')
                                    ->options(VehicleCondition::options())
                                    ->default(VehicleCondition::Used->value)
                                    ->required(),

                                Forms\Components\Select::make('body_type_id')
                                    ->label('Body type')
                                    ->relationship('bodyType', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Specs')
                            ->icon('heroicon-o-square-3-stack-3d')
                            ->schema([
                                Forms\Components\TextInput::make('year')
                                    ->label('Year')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1900)
                                    ->maxValue(now()->year + 1)
                                    ->required(),

                                Forms\Components\TextInput::make('mileage_km')
                                    ->label('Mileage (km)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->step(1000)
                                    ->minValue(0)
                                    ->required(),

                                Forms\Components\Select::make('fuel_type_id')
                                    ->label('Fuel type')
                                    ->relationship('fuelType', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('transmission_id')
                                    ->label('Transmission')
                                    ->relationship('transmission', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('exterior_color_id')
                                    ->label('Exterior colour')
                                    ->relationship('exteriorColor', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('interior_color_id')
                                    ->label('Interior colour')
                                    ->relationship('interiorColor', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('location')
                                    ->label('Location')
                                    ->maxLength(120),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Provenance')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\TextInput::make('owners_count')
                                    ->label('Previous owners')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(30),

                                Forms\Components\Select::make('accident_history')
                                    ->label('Accident history')
                                    ->options([
                                        'none' => 'No accidents',
                                        'minor' => 'Minor damage',
                                        'major' => 'Major damage',
                                        'unknown' => 'Unknown',
                                    ])->default('none'),

                                Forms\Components\TextInput::make('warranty')
                                    ->label('Warranty')
                                    ->maxLength(120),

                                Forms\Components\DatePicker::make('last_service_date')
                                    ->label('Last service'),

                                Forms\Components\TextInput::make('vin')
                                    ->label('VIN')
                                    ->maxLength(17)
                                    ->helperText('17 character vehicle identification number.'),

                                Forms\Components\TextInput::make('registration_number')
                                    ->label('Registration number')
                                    ->maxLength(40),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Finance')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('finance_deposit')
                                    ->label('Deposit')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->minValue(0),

                                Forms\Components\TextInput::make('finance_term_months')
                                    ->label('Term')
                                    ->numeric()
                                    ->integer()
                                    ->suffix('months')
                                    ->minValue(1)
                                    ->maxValue(120),

                                Forms\Components\TextInput::make('finance_apr')
                                    ->label('APR')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100),
                            ])->columns(3),

                        Forms\Components\Tabs\Tab::make('Contact override')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(40),

                                Forms\Components\TextInput::make('whatsapp')
                                    ->label('WhatsApp')
                                    ->tel()
                                    ->maxLength(40),

                                Forms\Components\TextInput::make('phone_display')
                                    ->label('Display label')
                                    ->maxLength(40)
                                    ->helperText('Shown next to the call button, e.g. "Call the dealer".'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Images')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Repeater::make('images')
                                    ->relationship()
                                    ->label('Gallery')
                                    ->orderColumn('sort_order')
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['alt'] ?? (isset($state['path']) ? basename($state['path']) : null))
                                    ->grid(2)
                                    ->defaultItems(0)
                                    ->schema([
                                        Forms\Components\FileUpload::make('path')
                                            ->label('Photo')
                                            ->directory('vehicles')
                                            ->disk('public')
                                            ->image()
                                            ->maxSize(10240)
                                            ->required(),
                                        Forms\Components\TextInput::make('alt')
                                            ->label('Alt text')
                                            ->maxLength(150),
                                        Forms\Components\Toggle::make('is_cover')
                                            ->label('Cover photo')
                                            ->inline(false),
                                    ]),
                            ])->columnSpanFull(),

                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Forms\Components\CheckboxList::make('features')
                                    ->label('Features')
                                    ->relationship('features', 'name')
                                    ->columns(3)
                                    ->searchable()
                                    ->default([]),
                            ])->columnSpanFull(),

                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Meta title')
                                    ->maxLength(60)
                                    ->helperText('Aim for 50–60 characters.')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta description')
                                    ->rows(3)
                                    ->maxLength(160)
                                    ->columnSpanFull(),
                            ])->columns(1),
                    ]),

                Forms\Components\Section::make('Publishing')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(VehicleStatus::options())
                            ->default(VehicleStatus::Draft->value)
                            ->required(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published at')
                            ->seconds(false),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured listing')
                            ->inline(false),
                    ])->columns(2),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('Cover')
                    ->disk('public')
                    ->getStateUsing(fn (Vehicle $record): ?string => $record->coverImage?->path_800w ?? $record->coverImage?->path)
                    ->height(56),

                Tables\Columns\TextColumn::make('make.name')
                    ->label('Make')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model.name')
                    ->label('Model')
                    ->searchable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(VehicleStatus::options()),

                Tables\Filters\SelectFilter::make('make')
                    ->label('Make')
                    ->relationship('make', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('condition')
                    ->label('Condition')
                    ->options(VehicleCondition::options()),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Vehicle $record): bool => $record->status !== VehicleStatus::Published
                        && auth()->user()?->can('publish', $record) === true)
                    ->action(fn (Vehicle $record) => $record->update([
                        'status' => VehicleStatus::Published,
                        'published_at' => $record->published_at ?? now(),
                    ])),

                Tables\Actions\Action::make('markSold')
                    ->label('Mark sold')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Vehicle $record): bool => $record->status === VehicleStatus::Published
                        && auth()->user()?->can('publish', $record) === true)
                    ->action(fn (Vehicle $record) => $record->update(['status' => VehicleStatus::Sold])),

                Tables\Actions\Action::make('feature')
                    ->label(fn (Vehicle $record): string => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon('heroicon-o-star')
                    ->color(fn (Vehicle $record): string => $record->is_featured ? 'warning' : 'gray')
                    ->visible(fn (Vehicle $record): bool => auth()->user()?->can('update', $record) === true)
                    ->action(fn (Vehicle $record) => $record->update(['is_featured' => ! $record->is_featured])),

                Tables\Actions\Action::make('specSheet')
                    ->label('Spec sheet')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (Vehicle $record): string => route('admin.vehicles.spec-sheet', $record))
                    ->visible(fn (Vehicle $record): bool => auth()->user()?->can('view', $record) === true),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->can('vehicle.publish') === true)
                        ->action(fn ($records) => $records->each->update([
                            'status' => VehicleStatus::Published,
                            'published_at' => now(),
                        ])),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['make', 'model', 'images']);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return trim($record->make?->name.' '.$record->model?->name.' '.$record->year);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
