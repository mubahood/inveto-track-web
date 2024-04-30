<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContributionRecord extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();

        //disable deleting
        static::deleting(function ($model) {
            throw new \Exception('Deleting is not allowed');
        });

        static::creating(function ($model) {

            $model->name = trim($model->name);
            $withSameName  = ContributionRecord::where([
                'name' => $model->name,
                'budget_program_id' => $model->budget_program_id,
            ])->first();
            if ($withSameName) {
                throw new \Exception('Name already exists');
            } 

        
            $model->chaned_by_id = auth()->user()->id;
            $model = self::prepare($model);
            return $model;
        });

        static::updating(function ($model) {
            $model->name = trim($model->name); 
            $withSameName  = ContributionRecord::where([
                'name' => $model->name,
                'budget_program_id' => $model->budget_program_id,
            ])->where('id', '!=', $model->id)->first(); 
            if ($withSameName) {
                throw new \Exception('Name already exists');
            } 
             

            //chaned_by_id as updated by
            $model->chaned_by_id = auth()->user()->id;
            $model = self::prepare($model);
            return $model;
        });

        //updated
        static::updated(function ($model) {
            self::finalizer($model);
        });

        //created
        static::created(function ($model) {
            self::finalizer($model);
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
        //budget_program_id if exists
        $budget_program = BudgetProgram::where('company_id', $loggedUser->company_id)->orderBy('id', 'desc')->first();
        if ($budget_program == null) {
            throw new \Exception('Budget program not found');
        }
        $custom_amount = (int) $data->custom_amount;
        if ($custom_amount > 0) {
            $data->amount = $custom_amount;
        }
        $custom_paid_amount = (int) $data->custom_paid_amount;
        if ($custom_paid_amount > 0) {
            $data->paid_amount = $custom_paid_amount;
        }

        if ($data->fully_paid == 'Yes') {
            $data->not_paid_amount = 0;
            $data->paid_amount = $data->amount;
        } else {
            $data->not_paid_amount = ((int)$data->amount) - ((int)$data->paid_amount);
        }

        if ($data->paid_amount >= $data->amount) {
            $data->fully_paid = 'Yes';
        } else {
            $data->fully_paid = 'No';
        }
        if ($data->fully_paid == 'Yes') {
            $data->not_paid_amount = 0;
        }
        return $data;
    }

    //public function finalizer
    public static function finalizer($data)
    {
        $table_name = (new self)->getTable();
        //sql set custom_paid_amount to null and custom_amount to null
        $sql = "UPDATE $table_name SET custom_paid_amount = NULL, custom_amount = NULL WHERE id = ?";
        DB::update($sql, [$data->id]);

        return $data;
    }

    //get treasurer initials
    public function tr()
    {
        $treasurer = User::find($this->treasurer_id);
        if ($treasurer == null) {
            return '';
        }
        //first letter of first name
        $first = substr($treasurer->name, 0, 1);
        return strtoupper($first);
    } 
}
