<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
        \App\Models\PaymentAllocation::observe(\App\Observers\PaymentAllocationObserver::class);
        \App\Models\StockMovement::observe(\App\Observers\StockMovementObserver::class);
        
        // Totals Automation
        \App\Models\JobOrderTask::observe(\App\Observers\JobOrderTaskObserver::class);
        \App\Models\PurchaseOrderItem::observe(\App\Observers\PurchaseOrderItemObserver::class);
        
        // Accounting Triggers
        \App\Models\PurchaseOrder::observe(\App\Observers\PurchaseOrderObserver::class);
        \App\Models\SalesOrder::observe(\App\Observers\SalesOrderObserver::class);
    }
}
