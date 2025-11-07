<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\StockItem::class => \App\Policies\StockItemPolicy::class,
        \App\Models\StockRecord::class => \App\Policies\StockItemPolicy::class,
        \App\Models\BudgetItem::class => \App\Policies\BudgetItemPolicy::class,
        \App\Models\BudgetProgram::class => \App\Policies\BudgetItemPolicy::class,
        \App\Models\FinancialRecord::class => \App\Policies\FinancialRecordPolicy::class,
        \App\Models\FinancialPeriod::class => \App\Policies\FinancialRecordPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
