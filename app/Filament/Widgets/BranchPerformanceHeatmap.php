<?php

namespace App\Filament\Widgets;

use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BranchPerformanceHeatmap extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('command.branch_heatmap_title');
    }

    public function table(Table $table): Table
    {
        $service = app(FinancialReportingService::class);
        $performance = $service->getBranchPerformance(Carbon::now());

        return $table
            ->query(
                \App\Models\Branch::query()->active()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('command.branch_name'))
                    ->searchable(['name_ar', 'name_en']),

                Tables\Columns\TextColumn::make('total_employees')
                    ->label(__('command.total_employees'))
                    ->state(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return $data['total_employees'] ?? 0;
                    }),

                Tables\Columns\TextColumn::make('on_time_rate')
                    ->label(__('command.on_time_rate'))
                    ->state(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['on_time_rate'] ?? 0) . '%';
                    })
                    ->color(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $rate = $data['on_time_rate'] ?? 0;
                        return match (true) {
                            $rate >= 95 => 'success',
                            $rate >= 85 => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('geofence_compliance')
                    ->label(__('command.geofence_compliance'))
                    ->state(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['geofence_compliance'] ?? 100) . '%';
                    })
                    ->color(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $rate = $data['geofence_compliance'] ?? 100;
                        return match (true) {
                            $rate >= 95 => 'success',
                            $rate >= 85 => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('monthly_loss')
                    ->label(__('command.monthly_loss'))
                    ->state(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return number_format($data['total_loss'] ?? 0, 2) . ' ' . __('command.sar');
                    })
                    ->color(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['total_loss'] ?? 0) > 0 ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('grade')
                    ->label(__('command.performance_grade'))
                    ->state(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $grade = $data['grade'] ?? 'average';
                        return __('command.grade_' . $grade);
                    })
                    ->color(function (\App\Models\Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $grade = $data['grade'] ?? 'average';
                        return match ($grade) {
                            'excellent' => 'success',
                            'good'      => 'info',
                            'average'   => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),
            ])
            ->paginated(false);
    }
}
