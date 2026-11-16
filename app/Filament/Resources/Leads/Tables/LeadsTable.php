<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Actions\Leads\AssignLead;
use App\Actions\Leads\ChangeLeadStatus;
use App\Actions\Leads\ExportLeadsCsv;
use App\Models\Lead;
use App\Models\ProductCategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('company')->searchable()->toggleable(),
                TextColumn::make('email')->label('Email address')->searchable(),
                TextColumn::make('country')->searchable()->toggleable(),
                TextColumn::make('source_product_title')->label('Product')->searchable()->toggleable(),
                TextColumn::make('source_category_slug')->label('Category')->badge()->toggleable(),
                TextColumn::make('source_slug')->searchable()->toggleable(),
                TextColumn::make('source_locale')->label('Locale')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->searchable(),
                TextColumn::make('assignee.email')->label('Assignee')->placeholder('Unassigned')->searchable(),
                TextColumn::make('first_response_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('last_activity_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'in_progress' => 'In progress',
                    'replied' => 'Replied',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('assigned_to')->label('Assignee')->options(fn (): array => self::assigneeOptions()),
                SelectFilter::make('country')->options(fn (): array => self::leadValues('country')),
                SelectFilter::make('source_category_slug')->label('Category')->options(fn (): array => ProductCategory::query()
                    ->orderBy('slug')->pluck('slug', 'slug')->unique()->all()),
                SelectFilter::make('source_slug')->label('Exact source SKU')->searchable()
                    ->options(fn (): array => self::leadValues('source_slug')),
                Filter::make('created_at')->form([
                    DatePicker::make('from')->label('Created from'),
                    DatePicker::make('until')->label('Created until'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('claim')
                    ->visible(fn (Lead $record): bool => $record->assigned_to === null)
                    ->requiresConfirmation()
                    ->action(fn (Lead $record) => app(AssignLead::class)->handle($record, auth()->user(), auth()->user())),
                Action::make('assign')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->form([
                        Select::make('assignee_id')->label('Assignee')->required()->searchable()
                            ->options(fn (): array => self::assigneeOptions()),
                    ])
                    ->fillForm(fn (Lead $record): array => ['assignee_id' => $record->assigned_to])
                    ->action(function (Lead $record, array $data): void {
                        app(AssignLead::class)->handle($record, User::findOrFail($data['assignee_id']), auth()->user());
                    }),
                Action::make('release')
                    ->visible(fn (Lead $record): bool => $record->assigned_to !== null
                        && ((int) $record->assigned_to === auth()->id() || (auth()->user()?->isSuperAdmin() ?? false)))
                    ->requiresConfirmation()
                    ->action(fn (Lead $record) => app(AssignLead::class)->handle($record, null, auth()->user())),
                Action::make('changeStatus')
                    ->label('Change status')
                    ->form([
                        Select::make('status')->required()->options([
                            'new' => 'New',
                            'in_progress' => 'In progress',
                            'replied' => 'Replied',
                            'archived' => 'Archived',
                        ]),
                    ])
                    ->fillForm(fn (Lead $record): array => ['status' => $record->status])
                    ->action(fn (Lead $record, array $data) => app(ChangeLeadStatus::class)
                        ->handle($record, $data['status'], auth()->user())),
            ])
            ->toolbarActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->action(fn ($livewire) => app(ExportLeadsCsv::class)->handle(
                        auth()->user(),
                        $livewire->tableFilters ?? [],
                    )),
                Action::make('exportWithNotes')
                    ->label('Export CSV with internal notes')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('Internal notes are confidential. Confirm this export includes them.')
                    ->action(fn ($livewire) => app(ExportLeadsCsv::class)->handle(
                        auth()->user(),
                        $livewire->tableFilters ?? [],
                        true,
                    )),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function assigneeOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->whereIn('role', [User::ROLE_SUPPORT, User::ROLE_SUPER_ADMIN])
            ->orderBy('name')
            ->pluck('email', 'id')
            ->all();
    }

    private static function leadValues(string $column): array
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return [];
        }

        return Lead::visibleTo($user)->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column, $column)->all();
    }
}
