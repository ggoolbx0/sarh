<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\WhistleblowerReport;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhistleblowerVaultPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    public static function getNavigationGroup(): ?string
    {
        return __('command.navigation_group');
    }

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.whistleblower-vault';

    protected static ?string $slug = 'whistleblower-vault';

    public static function getNavigationLabel(): string
    {
        return __('command.whistleblower_vault');
    }

    public function getTitle(): string
    {
        return __('command.vault_title');
    }

    public function getSubheading(): ?string
    {
        return __('command.vault_subtitle');
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WhistleblowerReport::query()->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label(__('command.wb_ticket'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('category')
                    ->label(__('command.wb_category'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fraud'        => 'danger',
                        'corruption'   => 'danger',
                        'harassment'   => 'warning',
                        'safety'       => 'info',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('severity')
                    ->label(__('command.wb_severity'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high'     => 'warning',
                        'medium'   => 'info',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('command.wb_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'           => 'info',
                        'under_review'  => 'warning',
                        'investigating' => 'warning',
                        'resolved'      => 'success',
                        'dismissed'     => 'gray',
                        default         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('command.triggered_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label(__('command.resolution_outcome'))
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new'           => __('command.status_new'),
                        'under_review'  => __('command.status_under_review'),
                        'investigating' => __('command.status_investigating'),
                        'resolved'      => __('command.status_resolved'),
                        'dismissed'     => __('command.status_dismissed'),
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => __('command.severity_critical'),
                        'high'     => __('command.severity_high'),
                        'medium'   => __('command.severity_medium'),
                        'low'      => __('command.severity_low'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_decrypted')
                    ->label(__('command.decrypted_content'))
                    ->icon('heroicon-o-eye')
                    ->color('danger')
                    ->modalHeading(fn (WhistleblowerReport $record) => $record->ticket_number)
                    ->modalContent(function (WhistleblowerReport $record) {
                        // Log vault access
                        AuditLog::record(
                            'vault_access',
                            $record,
                            description: __('command.audit_vault_access', ['ticket' => $record->ticket_number])
                        );

                        $decrypted = decrypt($record->encrypted_content);

                        return view('filament.pages.vault-content', [
                            'content'           => $decrypted,
                            'investigatorNotes' => $record->investigator_notes,
                            'resolutionOutcome' => $record->resolution_outcome,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('command.close')),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
