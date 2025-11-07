<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class StockSubCategory extends Model
{
    use HasFactory, BelongsToCompany, Cacheable;

    //fillables
    protected $fillable = [
        'company_id',
        'stock_category_id',
        'name',
        'description',
        'status',
        'image',
        'buying_price',
        'selling_price',
        'expected_profit',
        'earned_profit',
        'measurement_unit',
        'current_quantity',
        'reorder_level',
        'in_stock',
    ]; 
    


    //update_self
    public function update_self()
    {
        try {
            $active_financial_period = Utils::getActiveFinancialPeriod($this->company_id);
            if ($active_financial_period == null) {
                Log::warning("No active financial period found for company #{$this->company_id} when updating StockSubCategory #{$this->id}");
                return;
            }
            
            $total_buying_price = 0;
            $total_selling_price = 0;
            $current_quantity = 0;

            $stock_items = StockItem::where('stock_sub_category_id', $this->id)
                ->where('financial_period_id', $active_financial_period->id)
                ->get();

            foreach ($stock_items as $key => $value) {
                $total_buying_price += ($value->buying_price * $value->original_quantity);
                $total_selling_price += ($value->selling_price * $value->original_quantity);
                $current_quantity += $value->current_quantity;
            }

            $total_expected_profit = $total_selling_price - $total_buying_price;

            $this->buying_price = $total_buying_price;
            $this->selling_price = $total_selling_price;
            $this->expected_profit = $total_expected_profit;
            $this->current_quantity = $current_quantity;

            //check if in_stock
            if ($current_quantity > $this->reorder_level) {
                $this->in_stock = 'Yes';
            } else {
                $this->in_stock = 'No';
                if ($current_quantity > 0 && $current_quantity <= $this->reorder_level) {
                    Log::warning("Stock level low for sub-category '{$this->name}': {$current_quantity} {$this->measurement_unit} (Reorder level: {$this->reorder_level})");
                }
            }

            //earned_profit - sum all profit from stock records
            $this->earned_profit = StockRecord::where('stock_sub_category_id', $this->id)
                ->where('financial_period_id', $active_financial_period->id)
                ->sum('profit');

            $this->save();
            
            Log::info("StockSubCategory #{$this->id} aggregates updated successfully");
        } catch (\Exception $e) {
            Log::error("Error updating StockSubCategory #{$this->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function stockCategory()
    {
        return $this->belongsTo(StockCategory::class);
    }

    protected $appends = ['name_text'];

    //getter for name_text
    public function getNameTextAttribute()
    {
        $name_text = $this->name;
        if ($this->stockCategory != null) {
            $name_text =  $name_text . " - " . $this->stockCategory->name;
        }
        return $name_text;
    }
}
