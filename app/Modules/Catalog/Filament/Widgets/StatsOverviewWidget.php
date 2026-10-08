<?php

namespace App\Modules\Catalog\Filament\Widgets;

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Submissions\Models\VehicleSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $vehicles = Vehicle::query();
        $published = (clone $vehicles)->published()->count();
        $drafts = (clone $vehicles)->where('status', 'draft')->count();

        return [
            Stat::make('Published', $published)
                ->description('Live listings')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Drafts', $drafts)
                ->description('Awaiting publication')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color('warning'),

            Stat::make('New enquiries', Enquiry::query()->open()->count())
                ->description('Open follow-ups')
                ->descriptionIcon('heroicon-o-envelope')
                ->color('info'),

            Stat::make('Pending submissions', VehicleSubmission::query()->pending()->count())
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),
        ];
    }
}
