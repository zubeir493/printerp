<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use App\Services\SalesOrderItemImportService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('order_number')
                                    ->label('Sales Order #')
                                    ->default(function () {
                                        $lastOrder = \App\Models\SalesOrder::orderBy('id', 'desc')->first();
                                        $lastNumber = 0;

                                        if ($lastOrder && preg_match('/SO-(\d+)/', $lastOrder->order_number, $matches)) {
                                            $lastNumber = (int) $matches[1];
                                        }

                                        return 'SO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                                    })
                                    ->readOnly()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('partner_id')
                                    ->label('Customer')
                                    ->relationship('partner', 'name', modifyQueryUsing: fn ($query) => $query->where('is_customer', true))
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => \App\Models\Partner::where('id', 1)->first()?->id)
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('phone'),
                                        TextInput::make('address'),
                                        Hidden::make('is_customer')->default(true),
                                    ]),
                                Select::make('warehouse_id')
                                    ->label('Warehouse')
                                    ->relationship('warehouse', 'name')
                                    ->searchable()
                                    ->default(fn() => \App\Models\Warehouse::where('is_default', true)->value('id'))
                                    ->preload()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('order_date')
                                    ->default(now())
                                    ->required(),
                                Select::make('payment_mode')
                                    ->label('Payment Type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'credit' => 'Credit',
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->live(),
                                Select::make('payment_method')
                                    ->label('Collection Method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank' => 'Bank Transfer',
                                        'cheque' => 'Cheque',
                                    ])
                                    ->default('cash')
                                    ->visible(fn (Get $get) => $get('payment_mode') === 'cash')
                                    ->required(fn (Get $get) => $get('payment_mode') === 'cash'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('payment_reference')
                                    ->label('Payment Reference')
                                    ->visible(fn (Get $get) => $get('payment_mode') === 'cash')
                                    ->helperText('Optional receipt number or POS reference for immediate cash sales.'),
                                FileUpload::make('items_import_file')
                                    ->label('Import Sales Items')
                                    ->acceptedFileTypes([
                                        'text/csv',
                                        'application/csv',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->dehydrated(false)
                                    ->helperText('Headers supported: inventory_item_id or sku or name, plus quantity and unit_price.')
                                    ->afterStateUpdated(function (Set $set, Get $get, TemporaryUploadedFile | string | null $state) {
                                        if (! $state instanceof TemporaryUploadedFile) {
                                            return;
                                        }

                                        try {
                                            $rows = app(SalesOrderItemImportService::class)->importRows($state->getRealPath());

                                            $set('salesOrderItems', $rows);

                                            $subtotal = collect($rows)->sum('total');
                                            $set('subtotal', $subtotal);
                                            $set('total', $subtotal);

                                            Notification::make()
                                                ->title(count($rows) . ' sales item(s) imported')
                                                ->success()
                                                ->send();
                                        } catch (\Throwable $exception) {
                                            Notification::make()
                                                ->title('Unable to import sales items')
                                                ->body($exception->getMessage())
                                                ->danger()
                                                ->persistent()
                                                ->send();
                                        }
                                    }),
                            ]),
                        Repeater::make('salesOrderItems')
                            ->relationship('salesOrderItems')
                            ->label('Sale Items')
                            ->table([
                                TableColumn::make('Item')->width('220px')->alignLeft(),
                                TableColumn::make('Qty')->alignLeft(),
                                TableColumn::make('Unit Price')->alignLeft(),
                                TableColumn::make('Total')->alignLeft(),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->label('Item')
                                    ->relationship('inventoryItem', 'name', fn ($query) => $query->where('is_sellable', true))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $set('total', round((float) ($state ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                        \App\Filament\Support\Calculations::updateSubtotal($get, $set, '../../salesOrderItems', '../../subtotal');
                                        \App\Filament\Support\Calculations::updateSubtotal($get, $set, '../../salesOrderItems', '../../total');
                                    }),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->suffix('Birr')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $set('total', round((float) ($state ?? 0) * (float) ($get('quantity') ?? 0), 2));
                                        \App\Filament\Support\Calculations::updateSubtotal($get, $set, '../../salesOrderItems', '../../subtotal');
                                        \App\Filament\Support\Calculations::updateSubtotal($get, $set, '../../salesOrderItems', '../../total');
                                    }),
                                TextInput::make('total')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->suffix('Birr'),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addable(false)
                            ->extraItemActions([
                                \Filament\Actions\Action::make('add_item')
                                    ->label('Add Item')
                                    ->icon('heroicon-o-plus')
                                    ->action(function (Repeater $component) {
                                        $state = $component->getState() ?? [];
                                        $state[] = [
                                            'inventory_item_id' => null,
                                            'quantity' => 1,
                                            'unit_price' => 0,
                                            'total' => 0,
                                        ];
                                        $component->state($state);
                                    }),
                            ])
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                \App\Filament\Support\Calculations::updateSubtotal($get, $set, 'salesOrderItems', 'subtotal');
                                \App\Filament\Support\Calculations::updateSubtotal($get, $set, 'salesOrderItems', 'total');
                            })
                            ->deleteAction(
                                fn ($action) => $action->after(function (Get $get, Set $set) {
                                    \App\Filament\Support\Calculations::updateSubtotal($get, $set, 'salesOrderItems', 'subtotal');
                                    \App\Filament\Support\Calculations::updateSubtotal($get, $set, 'salesOrderItems', 'total');
                                })
                            ),
                    ])
                    ->columnSpan(3),
                Section::make('Summary')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Completed',
                                'void' => 'Void',
                            ])
                            ->default('draft')
                            ->required(),
                        TextInput::make('subtotal')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->suffix('Birr'),
                        TextInput::make('total')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->suffix('Birr'),
                        TextInput::make('paid_amount_display')
                            ->label('Paid Amount')
                            ->dehydrated(false)
                            ->readOnly()
                            ->suffix('Birr')
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $component->state($record ? $record->paid_amount : 0);
                            }),
                        TextInput::make('balance_display')
                            ->label('Balance')
                            ->dehydrated(false)
                            ->readOnly()
                            ->suffix('Birr')
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                $component->state($record ? $record->balance : 0);
                            }),
                    ]),
            ])
            ->columns(4);
    }
}
