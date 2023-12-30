<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utils
{
    static function getActiveFinancialPeriod($company_id)
    {
        return FinancialPeriod::where('company_id', $company_id)
            ->where('status', 'Active')->first();
    }
}
