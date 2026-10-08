<?php

namespace App\Modules\Leads\Filament\Resources;

use App\Modules\Leads\Enums\EnquiryStatus;
use App\Modules\Leads\Filament\Resources\EnquiryResource\Pages;
use App\Modules\Leads\Models\Enquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'enquiry';

    protected static ?string $pluralModelLabel = 'enquiries';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $badge = static::getModel()::query()->open()->count();

        return $badge > 0 ? (string) $badge : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Enquiry')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->disabled(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->disabled(),

                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->disabled(),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Vehicle')
                        ->relationship('vehicle', 'slug', modifyQueryUsing: fn (Builder $query) => $query->withTrashed())
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Message')
                ->schema([
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->rows(5)
                        ->columnSpanFull()
                        ->disabled(),
                ]),

            Forms\Components\Section::make('Follow up')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(EnquiryStatus::options())
                        ->default(EnquiryStatus::New->value)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columns(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vehicle.slug')
                    ->label('Vehicle')
                    ->description(fn (Enquiry $record): ?string => $record->vehicle
                        ? trim($record->vehicle->make?->name.' '.$record->vehicle->model?->name)
                        : null)
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnquiryStatus::options()),

                Tables\Filters\SelectFilter::make('vehicle')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'slug')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('contacted')
                    ->label('Mark contacted')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->visible(fn (Enquiry $record): bool => $record->status === EnquiryStatus::New
                        && auth()->user()?->can('update', $record) === true)
                    ->action(fn (Enquiry $record) => $record->update(['status' => EnquiryStatus::Contacted])),

                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Enquiry $record): bool => $record->status->isOpen()
                        && auth()->user()?->can('update', $record) === true)
                    ->action(fn (Enquiry $record) => $record->update(['status' => EnquiryStatus::Resolved])),

                Tables\Actions\Action::make('spam')
                    ->label('Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Enquiry $record): bool => $record->status->isOpen()
                        && auth()->user()?->can('update', $record) === true)
                    ->action(fn (Enquiry $record) => $record->update(['status' => EnquiryStatus::Spam])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolve')
                        ->label('Mark resolved')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()?->can('enquiry.update') === true)
                        ->action(fn ($records) => $records->each->update(['status' => EnquiryStatus::Resolved])),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('vehicle');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnquiries::route('/'),
            'edit' => Pages\EditEnquiry::route('/{record}/edit'),
        ];
    }
}
