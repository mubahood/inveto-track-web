<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    use HasFactory, BelongsToCompany, Cacheable;




    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            //check if company has a financial category with the same name
            $financial_category = FinancialCategory::where([
                ['company_id', '=', $model->company_id],
                ['name', '=', $model->name]
            ])->first();
            if ($financial_category != null) {
                return false;
            }
        });
    }
}
