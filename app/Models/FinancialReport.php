<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialReport extends Model
{
    use HasFactory;


    //static prepare
    static public function prepare($model)
    {
        $user = User::find($model->user_id);
        if ($user == null) {
            throw new \Exception("Invalid User");
        }
        $company = Company::find($user->company_id);
        if ($company == null) {
            throw new \Exception("Invalid Company");
        }
        $start_date = null;
        $end_date = null;
        $now = Carbon::parse($model->created_at);

        switch ($model->period_type) {
            case 'Today':
                $start_date = $now->startOfDay();
                $end_date = $now->endOfDay();
                break;
            case 'Yesterday':
                $start_date = $now->subDay()->startOfDay();
                $end_date = $now->endOfDay();
                break;
            case 'Week':
                $start_date = $now->startOfWeek();
                $end_date = $now->endOfWeek();
                break;
            case 'Month':
                $start_date = $now->startOfMonth();
                $end_date = $now->endOfMonth();
                break;
            case 'Cycle':
                $financial_period = Utils::getActiveFinancialPeriod($user->company_id);
                if ($financial_period == null) {
                    throw new \Exception("Financial Period is not active. Please activate the financial period.");
                }
                $start_date = Carbon::parse($financial_period->start_date);
                $end_date = Carbon::parse($financial_period->end_date);
                break;
            case 'Year':
                $start_date = $now->startOfYear();
                $end_date = $now->endOfYear();
                break;
            case 'Custom':
                $start_date = Carbon::parse($model->start_date);
                $end_date = Carbon::parse($model->end_date);
                break;
        }

        $model->start_date = $start_date;
        $model->end_date = $end_date;
        $model->company_id = $user->company_id;
        $model->currency = $company->currency;
        if ($model->type == 'Financial') {
            //total total_income from financial records where category is income and date is between start_date and end_date
            $total_income = FinancialRecord::where('company_id', $user->company_id)
                ->where('type', 'Income')
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('amount');
            //total total_expense from financial records where category is expense and date is between start_date and end_date
            $total_expense = FinancialRecord::where('company_id', $user->company_id)
                ->where('type', 'Expense')
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('amount');
            //profit = total_income - total_expense
            $model->profit = $total_income - $total_expense;
            //total_expense
            $model->total_expense = $total_expense;
            //total_income
            $model->total_income = $total_income;
            //include_finance_accounts
        } else if ($model->type == 'Inventory') {
            //inventory_total_buying_price
            $inventory_total_buying_price = StockCategory::where('company_id', $user->company_id)
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('total_buying_price');
            //inventory_total_selling_price
            $inventory_total_selling_price = StockCategory::where('company_id', $user->company_id)
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('total_selling_price');
            //inventory_total_expected_profit
            $inventory_total_expected_profit = StockCategory::where('company_id', $user->company_id)
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('expected_profit');
            //inventory_total_earned_profit
            $inventory_total_earned_profit = StockCategory::where('company_id', $user->company_id)
                ->whereBetween('date', [$start_date, $end_date])
                ->sum('earned_profit');
            $model->inventory_total_buying_price = $inventory_total_buying_price;
            $model->inventory_total_selling_price = $inventory_total_selling_price;
            $model->inventory_total_expected_profit = $inventory_total_expected_profit;
            $model->inventory_total_earned_profit = $inventory_total_earned_profit;
        }
        //sql that sets file_generated to No
        $sql = "UPDATE financial_reports SET file_generated = 'No' WHERE user_id = $user->id";
        DB::update($sql);
    }
}
