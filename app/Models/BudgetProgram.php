<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetProgram extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            //stop with same name and company_id is the same
            if (BudgetProgram::where('name', $model->name)->where('company_id', $model->company_id)->exists()) {
                throw new \Exception('Name already exists');
            }
            $model = self::prepare($model);
            return $model;
        });

        static::updating(function ($model) {
            //stop with same name but not the same id and company_id is the same
            if (BudgetProgram::where('name', $model->name)->where('id', '!=', $model->id)->where('company_id', $model->company_id)->exists()) {
                throw new \Exception('Name already exists');
            }
            $model = self::prepare($model);
            return $model;
        });
    }

    //public static function prepare
    public static function prepare($data)
    {
        $loggedUser = auth()->user();
        if ($loggedUser == null) {
            throw new \Exception('User not found');
        }
        $data->company_id = $loggedUser->company_id;
        return $data;
    }
}
