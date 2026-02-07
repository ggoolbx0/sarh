<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\TrapInteraction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class TrapAuditPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    public static function getNavigationGroup(): ?string
    {
        return __('command.navigation_group');
    }

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.trap-audit';

    protected static ?string $slug = 'trap-audit';

    public static function getNavigationLabel(): string
    {
        return __('command.trap_audit');
    }

    public function getTitle(): string
    {
        return __('command.audit_title');
    }

    /**
     * Security Gate: Level 10 only.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->is_super_admin || $user->security_level >= 10;
    }

    public function mount(): void
    {
        // Log access to trap audit
        AuditLog::record(
            'trap_audit_access',
            description: __('command.audit_trap_page_access')
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrapInteraction::query()
                    ->with(['user', 'trap', 'reviewer'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name_ar')
                    ->label(__('command.triggered_by'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.employee_id')
                    ->label(__('traps.employee_id')),

                Tables\Columns\TextColumn::make('trap.name')
                    ->label(__('command.trap_type'))
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('trap_type')
                    ->label(__('command.trap_type'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('risk_level')
                    ->label(__('command.risk_level'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'low'      => 'success',
                        'medium'   => 'warning',
                        'high'     => 'danger',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('page_url')
                    ->label(__('command.page_url'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('command.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_reviewed')
                    ->label(__('command.review_status'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('reviewer.name_ar')
                    ->label(__('command.reviewed_by'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('command.triggered_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.risk_score')
                    ->label(__('traps.risk_score'))
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        !$state || $state < 30  => 'success',
                        $state < 100 => 'warning',
                        $state < 300 => 'danger',
                        default      => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('risk_level')
                    ->options([
                        'low'      => __('command.severity_low'),
                        'medium'   => __('command.severity_medium'),
                        'high'     => __('command.severity_high'),
                        'critical' => __('command.severity_critical'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_reviewed')
                    ->label(__('command.review_status')),
            ])
            ->actions([
                Tables\Actions\Action::make('view_data')
                    ->label(__('command.interaction_data'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (TrapInteraction $record) => __('command.interaction_data'))
                    ->modalContent(function (TrapInteraction $record) {
                        return view('filament.pages.trap-interaction-detail', [
                            'interaction' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('command.close')),

                Tables\Actions\Action::make('mark_reviewed')
                    ->label(__('command.reviewed_by'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (TrapInteraction $record) => $record->is_reviewed)
                    ->action(function (TrapInteraction $record) {
                        $record->update([
                            'is_reviewed'  => true,
                            'reviewed_by'  => auth()->id(),
                            'reviewed_at'  => now(),
                        ]);

                        AuditLog::record(
                            'trap_reviewed',
                            $record,
                            description: __('command.audit_trap_reviewed')
                        );
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
