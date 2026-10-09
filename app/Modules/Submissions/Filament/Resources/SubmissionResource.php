<?php

namespace App\Modules\Submissions\Filament\Resources;

use App\Models\User;
use App\Modules\Submissions\Enums\SubmissionStatus;
use App\Modules\Submissions\Filament\Resources\SubmissionResource\Pages;
use App\Modules\Submissions\Models\VehicleSubmission;
use App\Modules\Submissions\Notifications\SubmissionRejected;
use App\Modules\Submissions\Services\SubmissionApprover;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SubmissionResource extends Resource
{
    protected static ?string $model = VehicleSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'submission';

    protected static ?string $pluralModelLabel = 'submissions';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $badge = static::getModel()::query()->pending()->count();

        return $badge > 0 ? (string) $badge : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Submitted by')
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
                ])->columns(3),

            Forms\Components\Section::make('Vehicle details')
                ->schema([
                    Forms\Components\Placeholder::make('data_view')
                        ->label('Submitted values')
                        ->columnSpanFull()
                        ->content(fn (VehicleSubmission $record): Htmlable => new HtmlString(collect($record->data ?? [])
                            ->map(fn ($value, $key) => '<strong>'.e(Str::headline((string) $key)).'</strong>: '.e(is_array($value) ? implode(', ', $value) : (string) $value))
                            ->implode('<br>'))),

                    Forms\Components\Placeholder::make('images_view')
                        ->label('Photos')
                        ->columnSpanFull()
                        ->content(fn (VehicleSubmission $record): Htmlable => new HtmlString(collect($record->images ?? [])
                            ->map(fn (string $path) => '<img src="'.e(Storage::disk('public')->url($path)).'" alt="" class="h-24 w-36 rounded-lg object-cover">')
                            ->implode(' ') ?: '<span class="text-sm text-gray-500">No photos attached.</span>')),
                ])->columns(1),

            Forms\Components\Section::make('Review')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(SubmissionStatus::options())
                        ->default(SubmissionStatus::Pending->value)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\TextColumn::make('images')
                    ->label('Photos')
                    ->state(fn (VehicleSubmission $record): int => count($record->images ?? []))
                    ->formatStateUsing(fn (int $state): string => $state.' file'.($state === 1 ? '' : 's')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SubmissionStatus::options()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve submission')
                    ->modalDescription('A draft vehicle will be created from this submission inside a database transaction.')
                    ->visible(fn (VehicleSubmission $record): bool => $record->status !== SubmissionStatus::Approved
                        && auth()->user()?->can('review', $record) === true)
                    ->action(function (VehicleSubmission $record, SubmissionApprover $approver): void {
                        try {
                            $approver->approve($record, self::approver());

                            Notification::make()
                                ->title('Submission approved')
                                ->body('A draft vehicle was created.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Approval failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for rejection')
                            ->rows(3)
                            ->required()
                            ->helperText('The submitter receives this by email, along with the submission reference.'),
                    ])
                    ->visible(fn (VehicleSubmission $record): bool => ! $record->status->isResolved()
                        && auth()->user()?->can('review', $record) === true)
                    ->action(function (VehicleSubmission $record, array $data, SubmissionApprover $approver): void {
                        $approver->decide($record, self::approver(), SubmissionStatus::Rejected, $data['reason']);

                        $record->notify(new SubmissionRejected($record, $data['reason']));

                        Notification::make()
                            ->title('Submission rejected')
                            ->body('The submitter has been emailed the reason.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('needsChanges')
                    ->label('Needs changes')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (VehicleSubmission $record): bool => ! $record->status->isResolved()
                        && auth()->user()?->can('review', $record) === true)
                    ->action(function (VehicleSubmission $record, SubmissionApprover $approver): void {
                        $approver->decide($record, self::approver(), SubmissionStatus::NeedsChanges);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    private static function approver(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('No authenticated user is available to review this submission.');
        }

        return $user;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }
}
