<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Actions\Leads\AddLeadNote;
use App\Models\Lead;
use App\Models\LeadNote;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    public function mount(): void
    {
        parent::mount();

        if (! Gate::allows('view', $this->getOwnerRecord())) {
            throw (new ModelNotFoundException)->setModel(Lead::class, [$this->getOwnerRecord()->getKey()]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label('Internal note')
                ->required()
                ->maxLength(5000)
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name'),
                TextColumn::make('body')->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('createNote')
                    ->label('Add note')
                    ->form($this->form(Schema::make($this))->getComponents())
                    ->action(function (array $data): void {
                        app(AddLeadNote::class)->handle(
                            $this->getOwnerRecord(),
                            $data['body'],
                            auth()->user(),
                        );
                    }),
            ]);
    }
}
