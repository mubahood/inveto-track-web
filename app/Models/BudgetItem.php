<?php

namespace App\Models;

use App\Traits\AuditLogger;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetItem extends Model
{
    use HasFactory, BelongsToCompany, AuditLogger;

    //boot
    protected static function boot()
    {
        parent::boot();

        //disable deleting
        static::deleting(function ($model) {
            //throw new \Exception('Deleting is not allowed');
        });

        static::creating(function ($model) {

            $model->name = trim($model->name);
            $withSameName  = BudgetItem::where([
                'name' => $model->name,
                'budget_item_category_id' => $model->budget_item_category_id,
            ])->first();

            if ($withSameName) {
                throw new \Exception('Name already exists');
            }


            $model = self::prepare($model);
            return $model;
        });

        static::updating(function ($model) {
            $model->name = trim($model->name);
            $withSameName  = BudgetItem::where([
                'name' => $model->name,
                'budget_item_category_id' => $model->budget_item_category_id,
            ])->where('id', '!=', $model->id)->first();
            if ($withSameName) {
                throw new \Exception('Name already exists');
            }

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
        $data->target_amount = $data->unit_price * $data->quantity;
        $loggedUser = User::find($data->created_by_id);
        if ($loggedUser == null) {
            throw new \Exception('User not found');
        }
        $data->company_id = $loggedUser->company_id;
        $data->changed_by_id = $loggedUser->id;
        $cat = BudgetItemCategory::find($data->budget_item_category_id);
        if ($cat == null) {
            throw new \Exception('Category not found');
        }
        return $data;
    }

    //public static function finalizer
    public static function finalizer($data)
    {
        $balance = $data->target_amount - $data->invested_amount;
        $is_complete = 'No';
        $percentage_done = 0;

        if ($data->target_amount == 0) {
            $percentage_done = 0;
        } else {
            $percentage_done = ($data->invested_amount / $data->target_amount) * 100;
        }

        if ($percentage_done >= 98) {
            $is_complete = 'Yes';
        } else {
            $is_complete = 'No';
        }
        
        // Use Eloquent update instead of raw SQL to prevent SQL injection
        $data->update([
            'balance' => $balance,
            'percentage_done' => $percentage_done,
            'is_complete' => $is_complete
        ]);
        
        $cat = BudgetItemCategory::find($data->budget_item_category_id);
        
        try {
            $cat->updateSelf();
        } catch (\Throwable $th) {
            //throw $th;
        }

        // Queue email notification to prevent blocking
        \App\Jobs\SendBudgetItemUpdateEmail::dispatch($data);
    }

    public function category()
    {
        return $this->belongsTo(BudgetItemCategory::class, 'budget_item_category_id');
    }
    //getter for budget_item_category_text
    public function getBudgetItemCategoryTextAttribute()
    {
        if ($this->category == null) {
            return 'N/A';
        }
        return $this->category->name;
    }

    //appends budget_item_category_text
    protected $appends = ['budget_item_category_text'];
}
