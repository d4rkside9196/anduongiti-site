<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer enquiry')->schema([
                TextEntry::make('name'),
                TextEntry::make('company')->placeholder('—'),
                TextEntry::make('email'),
                TextEntry::make('country')->placeholder('—'),
                TextEntry::make('message')->columnSpanFull(),
            ])->columns(2),
            Section::make('Product provenance')->schema([
                TextEntry::make('product'),
                TextEntry::make('source_slug')->placeholder('—'),
                TextEntry::make('source_product_title')->placeholder('—'),
                TextEntry::make('source_category_slug')->placeholder('—'),
                TextEntry::make('source_locale')->placeholder('—'),
            ])->columns(2),
            Section::make('Workflow')->schema([
                TextEntry::make('status')->badge(),
                TextEntry::make('assignee.email')->label('Assignee')->placeholder('Unassigned'),
                TextEntry::make('first_response_at')->dateTime()->placeholder('—'),
                TextEntry::make('last_activity_at')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->dateTime(),
            ])->columns(2),
        ]);
    }
}
