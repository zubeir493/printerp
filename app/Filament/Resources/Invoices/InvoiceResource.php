<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Resources\Resource;
use Filament\Tables\Table;
// use Filament\Forms\Form;
use App\Models\Invoice;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Invoices\Pages\ListInvoices::route('/'),
            'view' => \App\Filament\Resources\Invoices\Pages\ViewInvoice::route('/{record}'),
        ];
    }
}
