<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use HasFactory;


    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model = self::prepare($model);
            return $model;
        });

        static::updating(function ($model) {
            $model = self::prepare($model);
            return $model;
        });
    }

    static public function prepare($model)
    {
        $sub_category = StockSubCategory::find($model->stock_sub_category_id);
        if ($sub_category == null) {
            throw new \Exception("Invalid Stock Sub Category");
        }
        $model->stock_category_id = $sub_category->stock_category_id;


        $user = User::find($model->created_by_id);
        if ($user == null) {
            throw new \Exception("Invalid User");
        }
        $financial_period = Utils::getActiveFinancialPeriod($user->company_id);

        if ($financial_period == null) {
            throw new \Exception("Invalid Financial Period");
        }
        $model->financial_period_id = $financial_period->id;

        return $model;
    }






    //getter for gallery 
    public function getGalleryAttribute($value)
    {
        if ($value != null && strlen($value) > 3) {
            return json_decode($value);
        }
        return [];
    }

    //setter for gallery
    public function setGalleryAttribute($value)
    {
        $this->attributes['gallery'] = json_encode($value, true);
    }
}
