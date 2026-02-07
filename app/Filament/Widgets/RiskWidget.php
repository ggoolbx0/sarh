<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RiskWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('traps.risk_widget_title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('risk_score', '>', 0)
                    ->orderByDesc('risk_score')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('traps.employee')),

                Tables\Columns\TextColumn::make('employee_id')
                    ->label(__('traps.employee_id')),

                Tables\Columns\TextColumn::make('risk_score')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state < 30  => 'success',
                        $state < 100 => 'warning',
                        $state < 300 => 'danger',
                        default      => 'danger',
                    })
                    ->label(__('traps.risk_score')),

                Tables\Columns\TextColumn::make('risk_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    })
                    ->label(__('traps.risk_level')),

                Tables\Columns\TextColumn::make('trap_interactions_count')
                    ->counts('trapInteractions')
                    ->label(__('traps.total_triggers')),
            ])
            ->paginated(false);
    }
}
