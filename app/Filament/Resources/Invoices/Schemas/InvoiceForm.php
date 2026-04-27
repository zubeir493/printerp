<?php

namespace App\Filament\Resources\Invoices\Schemas;

// use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
// use Filament\Forms\Components\Grid;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                ComponentsSection::make('Invoice Information')
                    ->schema([
                        ComponentsGrid::make(2)
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->required()
                                    ->readOnly(),
                                
                                Select::make('invoice_type')
                                    ->label('Invoice Type')
                                    ->options([
                                        'sales' => 'Sales Invoice',
                                        'purchase' => 'Purchase Invoice', 
                                        'service' => 'Service Invoice',
                                        'receipt' => 'Payment Receipt',
                                    ])
                                    ->required(),
                            ]),
                        
                        ComponentsGrid::make(2)
                            ->schema([
                                DatePicker::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->required()
                                    ->readOnly(),
                                
                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required()
                                    ->readOnly(),
                            ]),
                    ]),
                
                ComponentsSection::make('Financial Information')
                    ->schema([
                        ComponentsGrid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->prefix('ETB')
                                    ->numeric()
                                    ->readOnly(),
                                
                                TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->prefix('ETB')
                                    ->numeric()
                                    ->readOnly(),
                                
                                TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->prefix('ETB')
                                    ->numeric()
                                    ->readOnly(),
                            ]),
                        
                        TextInput::make('balance_due')
                            ->label('Balance Due')
                            ->prefix('ETB')
                            ->numeric()
                            ->readOnly(),
                    ]),
                
                ComponentsSection::make('Email Information')
                    ->schema([
                        TextInput::make('email_recipient')
                            ->label('Email Recipient')
                            ->email()
                            ->readOnly(),
                        
                        Placeholder::make('emailed_at')
                            ->label('Last Emailed At'),
                    ]),
                
                ComponentsSection::make('System Information')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        
                        Textarea::make('tax_calculations')
                            ->label('Tax Calculations')
                            ->rows(3)
                            ->readOnly(),
                    ]),
            ]);
    }
}
