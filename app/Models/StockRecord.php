<?php

namespace App\Models;

use App\Traits\AuditLogger;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockRecord extends Model
{
    use HasFactory, BelongsToCompany, AuditLogger;
    /*         
            $table->foreignIdFor(Company::class);
            $table->foreignIdFor(StockItem::class);
            $table->foreignIdFor(StockCategory::class);
            $table->foreignIdFor(StockSubCategory::class);
            $table->foreignIdFor(User::class, 'created_by_id');
            $table->string('sku')->nullable();
            $table->string('name')->nullable();
            $table->string('measurement_unit');
            $table->string('description')->nullable();
            $table->string('type');
            $table->float('quantity');
            $table->float('selling_price');
            $table->float('total_sales'); */
    //fillables for above
    protected $fillable = [
        'company_id',
        'stock_item_id',
        'stock_category_id',
        'stock_sub_category_id',
        'financial_period_id',
        'created_by_id',
        'sku',
        'name',
        'measurement_unit',
        'description',
        'type',
        'quantity',
        'selling_price',
        'total_sales',
        'profit',
        'date',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Prepare model fields before saving (DON'T update stock quantities yet - that's done in 'created' event)
            $stock_item = StockItem::find($model->stock_item_id);
            if ($stock_item == null) {
                throw new \Exception("Invalid Stock Item selected.");
            }

            $financial_period = Utils::getActiveFinancialPeriod($stock_item->company_id);
            if ($financial_period == null) {
                throw new \Exception("No active Financial Period found. Please activate a financial period first.");
            }
            $model->financial_period_id = $financial_period->id;

            // Set basic fields from stock item
            $model->company_id = $stock_item->company_id;
            $model->stock_category_id = $stock_item->stock_category_id;
            $model->stock_sub_category_id = $stock_item->stock_sub_category_id;
            $model->sku = $stock_item->sku;
            $model->name = $stock_item->name;
            $model->measurement_unit = $stock_item->stockSubCategory->measurement_unit;
            
            // Set default description if not provided
            if ($model->description == null || trim($model->description) == '') {
                $model->description = $model->type . ' transaction';
            }
            
            // Validate and set date
            if ($model->date == null) {
                $model->date = date('Y-m-d');
            }
            
            // Validate quantity
            $quantity = abs($model->quantity);
            if ($quantity < 0.01) {
                throw new \Exception("Quantity must be greater than 0.");
            }
            $model->quantity = $quantity;

            // Validate transaction type
            $valid_types = ['Sale', 'Damage', 'Expired', 'Lost', 'Internal Use', 'Stock In', 'Adjustment', 'Other'];
            if (!in_array($model->type, $valid_types)) {
                throw new \Exception("Invalid transaction type. Must be one of: " . implode(', ', $valid_types));
            }

            // Calculate pricing and profit fields (but don't update stock yet)
            if ($model->type == 'Stock In' || $model->type == 'Adjustment') {
                // Adding stock
                $model->selling_price = $stock_item->selling_price;
                $model->total_sales = 0;
                $model->profit = 0;
                
            } else {
                // Stock Out (removing inventory)
                $model->selling_price = $stock_item->selling_price;
                $model->total_sales = $model->selling_price * $quantity;

                // Calculate profit for sales
                if ($model->type == 'Sale') {
                    $model->total_sales = abs($model->total_sales);
                    $model->profit = $model->total_sales - ($stock_item->buying_price * $quantity);
                } else {
                    // For non-sale transactions (damage, loss, etc.)
                    $model->total_sales = 0;
                    $model->profit = -($stock_item->buying_price * $quantity); // Record as loss
                }

                // Validate sufficient stock BEFORE attempting to save
                $current_quantity = $stock_item->current_quantity;
                if ($current_quantity < $quantity) {
                    throw new \Exception("Insufficient stock. Available: {$current_quantity} {$model->measurement_unit}, Requested: {$quantity} {$model->measurement_unit}");
                }
            }

            return true;
        });

        //created 
        static::created(function ($model) {
            return DB::transaction(function () use ($model) {
                
                $stock_item = StockItem::find($model->stock_item_id);
                if ($stock_item == null) {
                    throw new \Exception("Invalid Stock Item.");
                }
                
                // UPDATE STOCK QUANTITIES - This runs AFTER the record is successfully saved
                $quantity = abs($model->quantity);
                
                if ($model->type == 'Stock In' || $model->type == 'Adjustment') {
                    // Adding stock
                    $new_quantity = $stock_item->current_quantity + $quantity;
                    $stock_item->current_quantity = $new_quantity;
                    $stock_item->save();
                    
                    Log::info("Stock In: Added {$quantity} units to item #{$stock_item->id}. New quantity: {$new_quantity}");
                    
                } else {
                    // Stock Out (removing inventory)
                    $new_quantity = $stock_item->current_quantity - $quantity;
                    $stock_item->current_quantity = $new_quantity;
                    $stock_item->save();
                    
                    Log::info("Stock Out ({$model->type}): Removed {$quantity} units from item #{$stock_item->id}. New quantity: {$new_quantity}");
                }
                
                // Update aggregates
                $stock_item->stockSubCategory->update_self();
                $stock_item->stockSubCategory->stockCategory->update_self();

                $company = Company::find($model->company_id);
                if ($company == null) {
                    throw new \Exception("Invalid Company.");
                }

                // Create financial records based on transaction type
                if ($model->type == 'Sale') {
                    // Record income for sales
                    $financial_category = FinancialCategory::where([
                        ['company_id', '=', $company->id],
                        ['name', '=', 'Sales']
                    ])->first();
                    
                    if ($financial_category == null) {
                        Company::prepare_account_categories($company->id);
                        $financial_category = FinancialCategory::where([
                            ['company_id', '=', $company->id],
                            ['name', '=', 'Sales']
                        ])->first();
                        if ($financial_category == null) {
                            throw new \Exception("Sales Account Category not found.");
                        }
                    }
                    
                    $fin_rec = new FinancialRecord();
                    $fin_rec->financial_category_id = $financial_category->id;
                    $fin_rec->company_id = $company->id;
                    $fin_rec->user_id = $model->created_by_id;
                    $fin_rec->created_by_id = $model->created_by_id;
                    $fin_rec->amount = $model->total_sales;
                    $fin_rec->quantity = $model->quantity;
                    $fin_rec->type = 'Income';
                    $fin_rec->payment_method = 'Cash';
                    $fin_rec->recipient = '';
                    $fin_rec->receipt = '';
                    $fin_rec->date = $model->date;
                    $fin_rec->description = 'Stock sale: ' . $model->name . ' (Record #' . $model->id . ')';
                    $fin_rec->financial_period_id = $model->financial_period_id;
                    $fin_rec->save();
                    
                    Log::info("Financial record created for sale #" . $model->id);
                    
                } elseif (in_array($model->type, ['Damage', 'Expired', 'Lost'])) {
                    // Record expense for losses
                    $financial_category = FinancialCategory::where([
                        ['company_id', '=', $company->id],
                        ['name', '=', 'Expense']
                    ])->first();
                    
                    if ($financial_category == null) {
                        Company::prepare_account_categories($company->id);
                        $financial_category = FinancialCategory::where([
                            ['company_id', '=', $company->id],
                            ['name', '=', 'Expense']
                        ])->first();
                    }
                    
                    if ($financial_category != null) {
                        $stock_item = StockItem::find($model->stock_item_id);
                        $loss_amount = $stock_item->buying_price * $model->quantity;
                        
                        $fin_rec = new FinancialRecord();
                        $fin_rec->financial_category_id = $financial_category->id;
                        $fin_rec->company_id = $company->id;
                        $fin_rec->user_id = $model->created_by_id;
                        $fin_rec->created_by_id = $model->created_by_id;
                        $fin_rec->amount = $loss_amount;
                        $fin_rec->quantity = $model->quantity;
                        $fin_rec->type = 'Expense';
                        $fin_rec->payment_method = 'N/A';
                        $fin_rec->recipient = '';
                        $fin_rec->receipt = '';
                        $fin_rec->date = $model->date;
                        $fin_rec->description = 'Stock loss (' . $model->type . '): ' . $model->name . ' (Record #' . $model->id . ')';
                        $fin_rec->financial_period_id = $model->financial_period_id;
                        $fin_rec->save();
                        
                        Log::info("Financial record created for stock loss #" . $model->id);
                    }
                } elseif ($model->type == 'Stock In') {
                    // Record expense for stock purchases
                    $financial_category = FinancialCategory::where([
                        ['company_id', '=', $company->id],
                        ['name', '=', 'Purchase']
                    ])->first();
                    
                    if ($financial_category == null) {
                        Company::prepare_account_categories($company->id);
                        $financial_category = FinancialCategory::where([
                            ['company_id', '=', $company->id],
                            ['name', '=', 'Purchase']
                        ])->first();
                    }
                    
                    if ($financial_category != null) {
                        $stock_item = StockItem::find($model->stock_item_id);
                        $purchase_amount = $stock_item->buying_price * $model->quantity;
                        
                        $fin_rec = new FinancialRecord();
                        $fin_rec->financial_category_id = $financial_category->id;
                        $fin_rec->company_id = $company->id;
                        $fin_rec->user_id = $model->created_by_id;
                        $fin_rec->created_by_id = $model->created_by_id;
                        $fin_rec->amount = $purchase_amount;
                        $fin_rec->quantity = $model->quantity;
                        $fin_rec->type = 'Expense';
                        $fin_rec->payment_method = 'Cash';
                        $fin_rec->recipient = '';
                        $fin_rec->receipt = '';
                        $fin_rec->date = $model->date;
                        $fin_rec->description = 'Stock purchase: ' . $model->name . ' (Record #' . $model->id . ')';
                        $fin_rec->financial_period_id = $model->financial_period_id;
                        $fin_rec->save();
                        
                        Log::info("Financial record created for stock purchase #" . $model->id);
                    }
                }
            });
        });

        // Handle record deletion - restore stock and cleanup
        static::deleting(function ($model) {
            return DB::transaction(function () use ($model) {
                
                $stock_item = StockItem::find($model->stock_item_id);
                if ($stock_item == null) {
                    Log::warning("Stock item not found when deleting record #{$model->id}");
                    return true; // Allow deletion even if item is gone
                }

                // Reverse the stock quantity change
                if ($model->type == 'Stock In' || $model->type == 'Adjustment') {
                    // Was an addition, so subtract
                    $stock_item->current_quantity -= $model->quantity;
                    Log::info("Deleting Stock In record #{$model->id}: Removing {$model->quantity} units from item #{$stock_item->id}");
                } else {
                    // Was a removal, so add back
                    $stock_item->current_quantity += $model->quantity;
                    Log::info("Deleting Stock Out record #{$model->id}: Restoring {$model->quantity} units to item #{$stock_item->id}");
                }
                
                $stock_item->save();

                // Delete associated financial records
                FinancialRecord::where('description', 'like', '%Record #' . $model->id . '%')->delete();
                
                Log::info("Stock record #{$model->id} deleted and stock restored");
            });
        });

        // Update aggregates after deletion
        static::deleted(function ($model) {
            $stock_item = StockItem::find($model->stock_item_id);
            if ($stock_item != null && $stock_item->stockSubCategory != null) {
                $stock_item->stockSubCategory->update_self();
                if ($stock_item->stockSubCategory->stockCategory != null) {
                    $stock_item->stockSubCategory->stockCategory->update_self();
                }
            }
        });
    }

    /* 
    									


    */
}
