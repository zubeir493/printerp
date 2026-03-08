<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->schema([
                    Repeater::make('JournalItems')
                        ->relationship('JournalItems') // This is the magic link!
                        ->table([
                            TableColumn::make('Account')->alignLeft(),
                            TableColumn::make('Debit')->alignLeft(),
                            TableColumn::make('Credit')->alignLeft(),
                            TableColumn::make('Party')->alignRight(),
                        ])
                        ->schema([
                            Select::make('account_id')
                                ->relationship('account', 'name')
                                ->options(function () {
                                    return \App\Models\Account::all()
                                        ->groupBy('type')
                                        ->map(function ($accounts) {
                                            return $accounts->mapWithKeys(fn($a) => [$a->id => "{$a->code} - {$a->name}"]);
                                        });
                                })
                                ->searchable()
                                ->required(),
                            TextInput::make('debit')->numeric()->default(0)->suffix('Birr')->live(),
                            TextInput::make('credit')->numeric()->default(0)->suffix('Birr')->live(),
                            Select::make('party')
                                ->label('Party')
                                ->options([
                                    '🤝 Partners' => \App\Models\Partner::pluck('name', 'id')
                                        ->mapWithKeys(fn($n, $id) => ["Partner:{$id}" => "{$n}"])
                                        ->toArray(),
                                    '📑 Job Orders' => \App\Models\JobOrder::whereIn('status', ['design', 'production'])
                                        ->pluck('job_order_number', 'id')
                                        ->mapWithKeys(fn($n, $id) => ["JobOrder:{$id}" => "#{$n}"])
                                        ->toArray(),
                                ])
                                ->searchable(),

                        ])
                        ->minItems(2)->defaultItems(2)->columnSpanFull()->compact()->addActionLabel('Add line')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $items = $get('JournalItems') ?? [];

                            $totalDebit = collect($items)->sum(fn($item) => (float) ($item['debit'] ?? 0));
                            $totalCredit = collect($items)->sum(fn($item) => (float) ($item['credit'] ?? 0));

                            $set('total_debit', $totalDebit);
                            $set('total_credit', $totalCredit);
                        })
                        // Handle recalculation when a row is removed
                        ->deleteAction(
                            fn($action) => $action->after(function (Get $get, Set $set) {
                                $items = $get('JournalItems') ?? [];
                                $set('total_debit', collect($items)->sum(fn($item) => (float) ($item['debit'] ?? 0)));
                                $set('total_credit', collect($items)->sum(fn($item) => (float) ($item['credit'] ?? 0)));
                            })
                        ),
                ])->columnSpanFull()->columns(2),
                Section::make('Additional Information')->schema([
                    Group::make()->schema([
                        Group::make()->schema([
                            TextInput::make('reference')
                                ->placeholder('PT-CASH-01-2026')
                                ->required(),
                            DatePicker::make('date')
                                ->default(now())
                                ->required(),
                        ])->columns(2),
                        FileUpload::make('attachment')->maxSize(5120)->disk('public')->directory('attachments'),
                        Textarea::make('narration')->columnSpanFull()
                    ])->columnSpan(3),
                    Group::make()->schema([
                        TextInput::make('total_debit')->numeric()->default(0)->suffix('Birr')->disabled()->dehydrated()->same('total_credit'),
                        TextInput::make('total_credit')->numeric()->default(0)->suffix('Birr')->disabled()->dehydrated()
                    ])->columnSpan(2)
                ])->columnSpanFull()->columns(5),
            ])->columns(3)->alignCenter();
    }
}
