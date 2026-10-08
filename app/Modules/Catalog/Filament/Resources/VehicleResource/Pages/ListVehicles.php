<?php

namespace App\Modules\Catalog\Filament\Resources\VehicleResource\Pages;

use App\Modules\Catalog\Filament\Resources\VehicleResource;
use App\Modules\Catalog\Imports\VehicleImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('export')
                ->label('Export XLSX')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('vehicle.view') === true)
                ->url(route('admin.inventory.export')),

            Actions\Action::make('import')
                ->label('Import XLSX')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('vehicle.create') === true)
                ->modalDescription('Rows are matched on the slug column. Unknown makes and models are created automatically.')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Inventory spreadsheet')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')
                        ->directory('imports')
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'] ?? null;
                    $path = is_array($path) ? ($path[0] ?? null) : $path;

                    if (blank($path)) {
                        Notification::make()->title('No file selected')->warning()->send();

                        return;
                    }

                    try {
                        Excel::import(
                            new VehicleImport(auth()->user()?->id),
                            Storage::disk('local')->path($path)
                        );

                        Notification::make()->title('Inventory imported')->success()->send();
                        $this->redirect($this->getUrl());
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Import failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
