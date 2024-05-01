<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BudgetItemCategory extends Model
{
    use HasFactory;



    //update self
    public function updateSelf()
    {
        $target_amount = BudgetItem::where('budget_item_category_id', $this->id)->sum('target_amount');
        $invested_amount = BudgetItem::where('budget_item_category_id', $this->id)->sum('invested_amount');
        $table = (new BudgetItemCategory())->getTable();
        $balance = $target_amount - $invested_amount;
        $sql = "UPDATE {$table} SET target_amount = $target_amount, 
        balance = $balance, 
        invested_amount = $invested_amount WHERE id = $this->id";
        DB::update($sql);
    }
}
