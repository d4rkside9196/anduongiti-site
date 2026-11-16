<?php

namespace App\Filament\Resources\CoffeeProducts\Tables;

use App\Actions\Catalog\DuplicateCoffeeProduct;
use App\Actions\Catalog\SetCatalogVisibility;
use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CoffeeProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                ImageColumn::make('image')
                    ->extraImgAttributes(fn (CoffeeProduct $record): array => ['alt' => $record->image_alt ?: $record->title])
                    ->getStateUsing(fn ($record) => $record->image_url)
                    ->defaultImageUrl(fn ($record) => asset('uploads/coffee/'.($record->slug ?: 'placeholder').'.svg'))
                    ->circular(false)
                    ->square(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('bean_variety')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (CoffeeProduct::BEAN_VARIETY_LABELS[$state] ?? $state) : null),
                TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('visible')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => ProductCategory::ordered()->get()
                        ->mapWithKeys(fn (ProductCategory $category): array => [
                            $category->id => $category->name.' ('.$category->locale.')',
                        ])
                        ->all()),
                SelectFilter::make('locale')->options(['en' => 'English', 'vi' => 'Vietnamese']),
                TernaryFilter::make('visible'),
            ])
            ->recordActions([
                Action::make('duplicateToLocale')
                    ->label(fn (CoffeeProduct $record): string => 'Duplicate to '.($record->locale === 'en' ? 'Vietnamese' : 'English'))
                    ->visible(fn (CoffeeProduct $record): bool => in_array($record->locale, ['en', 'vi'], true)
                        && ! CoffeeProduct::query()
                            ->where('slug', $record->slug)
                            ->where('locale', $record->locale === 'en' ? 'vi' : 'en')
                            ->exists())
                    ->action(function (CoffeeProduct $record): void {
                        $targetLocale = $record->locale === 'en' ? 'vi' : 'en';

                        try {
                            $draft = app(DuplicateCoffeeProduct::class)->handle($record, $targetLocale);

                            Notification::make()
                                ->title('Translation draft created')
                                ->body($draft->title.' ('.$draft->locale.') is hidden until translation is complete.')
                                ->success()
                                ->send();
                        } catch (DomainException $exception) {
                            Notification::make()
                                ->title('Cannot duplicate product')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
