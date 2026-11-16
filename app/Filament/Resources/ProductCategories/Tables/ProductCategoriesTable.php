<?php

namespace App\Filament\Resources\ProductCategories\Tables;

use App\Actions\Catalog\SetCatalogVisibility;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('locale')->badge()->sortable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('visible')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('locale')->options(['en' => 'English', 'vi' => 'Vietnamese']),
                TernaryFilter::make('visible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::visibilityAction('show', true),
                    self::visibilityAction('hide', false),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function visibilityAction(string $name, bool $visible): BulkAction
    {
        return BulkAction::make($name)
            ->label(ucfirst($name).' selected')
            ->requiresConfirmation()
            ->action(function (Collection $records) use ($visible): void {
                $updated = 0;
                $action = app(SetCatalogVisibility::class);

                foreach ($records as $record) {
                    if ($record->visible === $visible) {
                        continue;
                    }

                    $action->handle($record, $visible);
                    $updated++;
                }

                Notification::make()
                    ->title($updated.' '.($updated === 1 ? 'record' : 'records').' '.($visible ? 'shown' : 'hidden').'.')
                    ->success()
                    ->send();
            });
    }
}
