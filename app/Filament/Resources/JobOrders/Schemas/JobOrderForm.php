<?php

namespace App\Filament\Resources\JobOrders\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;
use Filament\Schemas\Components\Utilities\Set as UtilitiesSet;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class JobOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('partner_id')
                                    ->label('Customer')
                                    ->relationship('partner', 'name', modifyQueryUsing: fn($query) => $query->where('is_customer', true))
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('phone')
                                            ->required(),
                                        TextInput::make('address'),
                                        Hidden::make('is_customer')->default(true),
                                    ])
                                    ->searchable()
                                    ->required(),
                                TextInput::make('job_order_number')
                                    ->required()
                            ]),
                        Grid::make()
                            ->schema([
                                Select::make('job_type')
                                    ->options([
                                        'books' => 'Book Printing',
                                        'packages' => 'Package Printing',
                                    ])
                                    ->reactive()
                                    ->default('packages')
                                    ->required(),
                                Select::make('production_mode')
                                    ->options([
                                        'make_to_order' => 'Make to Order (Client Job)',
                                        'make_to_stock' => 'Make to Stock (Internal)',
                                    ])
                                    ->default('make_to_order')
                                    ->required(),
                                DatePicker::make('submission_date')
                                    ->default(now())
                                    ->required(),
                            ])
                            ->columns(3),
                        Repeater::make('jobOrderTasks')
                            ->label('List of Tasks')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->required(),

                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric(),

                                TextInput::make('unit_cost')
                                    ->label('Cost')
                                    ->numeric()
                                    ->suffix('Birr')
                                    ->required()
                                    ->live(),

                                Repeater::make('paper')
                                    ->label('Paper used for this task')
                                    ->table([
                                        TableColumn::make('Paper')->alignLeft(),
                                        TableColumn::make('Required qty (sheets)')->alignLeft(),
                                        TableColumn::make('Reserve qty (sheets)')->alignLeft(),
                                    ])
                                    ->compact()
                                    ->schema([
                                        Select::make('inventory_item_id')
                                            ->label('Material')
                                            ->options(\App\Models\InventoryItem::pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $item = \App\Models\InventoryItem::find($state);
                                                $set('base_unit', $item?->unit);
                                            }),
                                        TextInput::make('required_quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(0),

                                        TextInput::make('reserve_quantity')
                                            ->numeric()
                                            ->default(0),

                                        Hidden::make('base_unit'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(1)
                                    ->columnSpanFull()
                                    ->addActionLabel('Add paper')
                                    ->cloneable()
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->minItems(1),
                            ])
                            ->columns(3)
                            ->required()
                            ->defaultItems(1)
                            ->addable(false)
                            ->cloneable(true)
                            ->minItems(1)
                            ->cloneAction(fn(Action $action) => $action->icon('heroicon-s-document-plus'))
                            ->live() // 👈 REQUIRED
                            ->afterStateUpdated(function (UtilitiesGet $get, UtilitiesSet $set) {
                                $total = collect($get('jobOrderTasks'))
                                    ->sum(fn($job_order_task) => (float) ($job_order_task['unit_cost'] ?? 0));

                                $set('total_price', $total);

                                $percentage = (float) ($get('advance_percentage') ?? 0);
                                $set('advance_amount', $total * ($percentage / 100));
                            })
                            ->deleteAction(
                                fn($action) => $action->after(function (UtilitiesGet $get, UtilitiesSet $set) {
                                    $total = collect($get('jobOrderTasks'))
                                        ->sum(fn($job_order_task) => (float) ($job_order_task['unit_cost'] ?? 0));

                                    $set('total_price', $total);

                                    $percentage = (float) ($get('advance_percentage') ?? 0);
                                    $set('advance_amount', $total * ($percentage / 100));
                                })
                            ),
                        Grid::make(2)
                            ->schema([
                                CheckboxList::make('services')
                                    ->label('Additional Services')
                                    ->options(fn(callable $get) => match ($get('job_type')) {
                                        'books' => [
                                            'typing' => 'Typing',
                                            'layout_design' => 'Editing',
                                            'cover_design' => 'Cover Design',
                                            'selling_price' => 'Insert selling price on the book cover',
                                            'spine_has_text' => 'Spine has text',
                                            'cover_inner_printing' => 'Cover inner printing',
                                            'dont_insert_printer_name' => 'Don\'t insert printer name on the book cover',
                                        ],
                                        'packages' => [
                                            'new_design' => 'New Design',
                                            'redesign' => 'Redesign',
                                            'new_dielines' => 'New dielines',
                                            'old_dielines' => 'Old dielines',
                                            'full-color' => 'Full color',
                                            'one_side_print' => 'One side print',
                                        ],
                                        default => [],
                                    })
                                    ->columns(2),
                                Textarea::make('remarks'),
                            ]),
                        Section::make('Production Outputs')
                            ->description('Specify the finished goods produced and destination warehouse.')
                            ->visible(fn(UtilitiesGet $get) => $get('production_mode') === 'make_to_stock')
                            ->schema([
                                Repeater::make('outputs')
                                    ->relationship()
                                    ->schema([
                                        Select::make('inventory_item_id')
                                            ->label('Finished Good')
                                            ->options(\App\Models\InventoryItem::where('type', 'finished_good')->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(),
                                        Select::make('warehouse_id')
                                            ->label('Destination Warehouse')
                                            ->relationship('warehouse', 'name')
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(0),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Output')
                            ])
                    ])->columnSpan(3),

                Section::make()
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'design' => 'Design',
                                'production' => 'Production',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            // ->disableOptionWhen(
                            //     fn(string $value, $record): bool =>
                            //     $value === 'production' && ($record ? !$record->canStartProduction() : true)
                            // )
                            ->default('draft')
                            ->required(),
                        FileUpload::make('cost_calc_file')
                            ->label('Cost Calculation File')
                            ->directory('cost_calculations')
                            ->acceptedFileTypes(['application/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->maxSize(1024)
                            ->panelAspectRatio('3:1')
                            ->downloadable()
                            ->required(),
                        Toggle::make('advance_paid')
                            ->label('Advance Paid?')
                            ->default(true),
                        TextInput::make('advance_amount')
                            ->label('Advance Amount')
                            ->default(0)
                            ->numeric()
                            ->suffix(' Birr'),
                        TextInput::make('total_price')
                            ->label('Total')
                            ->default(0)
                            ->suffix(' Birr')
                            ->readOnly()
                            ->numeric()
                    ]),
                Section::make('Materials Overview')
                    ->description('Operational visibility of material consumption.')
                    ->visible(fn($record) => $record !== null)
                    ->schema([
                        Repeater::make('materials_dashboard')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->statePath('materials_summary') // We'll compute this state
                            ->schema([
                                TextInput::make('material_name')->label('Material')->readOnly(),
                                TextInput::make('required')->label('Required')->readOnly(),
                                TextInput::make('issued')->label('Issued')->readOnly(),
                                TextInput::make('remaining')->label('Remaining')->readOnly(),
                                TextInput::make('overconsumed')->label('Overconsumed')->readOnly(),
                                TextInput::make('completion')->label('Completion %')->readOnly(),
                            ])
                            ->columns(6)
                    ]),
            ])->columns(4);
    }
}
