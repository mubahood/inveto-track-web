<?php

namespace App\Models;

use App\Traits\AuditLogger;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockItem extends Model
{
    use HasFactory, BelongsToCompany, AuditLogger;


    //fillables
    protected $fillable = [
        'company_id',
        'created_by_id',
        'stock_category_id',
        'stock_sub_category_id',
        'financial_period_id',
        'name',
        'description',
        'image',
        'barcode',
        'sku',
        'generate_sku',
        'update_sku',
        'gallery',
        'buying_price',
        'selling_price',
        'original_quantity',
        'current_quantity',
    ];
    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            return DB::transaction(function () use ($model) {
                $model = self::prepare($model);
                $model->current_quantity = $model->original_quantity;
                
                Log::info("Creating stock item: {$model->name} with quantity {$model->original_quantity}");
                return $model;
            });
        });

        static::updating(function ($model) {
            return DB::transaction(function () use ($model) {
                $original_model = self::find($model->id);
                $model = self::prepare($model);
                
                // Prevent manual modification of current_quantity through edit
                // It should only change through StockRecord transactions
                if ($original_model) {
                    $model->current_quantity = $original_model->current_quantity;
                }
                
                Log::info("Updating stock item #{$model->id}: {$model->name}");
                return $model;
            });
        });

        static::created(function ($model) {
            DB::transaction(function () use ($model) {
                $stock_category = StockCategory::find($model->stock_category_id);
                if ($stock_category) {
                    $stock_category->update_self();
                }

                $stock_sub_category = StockSubCategory::find($model->stock_sub_category_id);
                if ($stock_sub_category) {
                    $stock_sub_category->update_self();
                }
                
                Log::info("Stock item created successfully: #{$model->id}");
            });
        });

        static::updated(function ($model) {
            DB::transaction(function () use ($model) {
                $stock_category = StockCategory::find($model->stock_category_id);
                if ($stock_category) {
                    $stock_category->update_self();
                }

                $stock_sub_category = StockSubCategory::find($model->stock_sub_category_id);
                if ($stock_sub_category) {
                    $stock_sub_category->update_self();
                }
                
                Log::info("Stock item updated successfully: #{$model->id}");
            });
        });

        static::deleting(function ($model) {
            // Prevent deletion if there are stock records
            $record_count = StockRecord::where('stock_item_id', $model->id)->count();
            if ($record_count > 0) {
                throw new \Exception("Cannot delete stock item with existing stock records. Found {$record_count} record(s). Please delete the records first.");
            }
            
            // Prevent deletion if there's still stock
            if ($model->current_quantity > 0) {
                throw new \Exception("Cannot delete stock item with remaining stock. Current quantity: {$model->current_quantity} {$model->stockSubCategory->measurement_unit}. Please record stock out transactions first.");
            }
            
            Log::warning("Deleting stock item #{$model->id}: {$model->name}");
        });

        static::deleted(function ($model) {
            DB::transaction(function () use ($model) {
                $stock_category = StockCategory::find($model->stock_category_id);
                if ($stock_category) {
                    $stock_category->update_self();
                }

                $stock_sub_category = StockSubCategory::find($model->stock_sub_category_id);
                if ($stock_sub_category) {
                    $stock_sub_category->update_self();
                }
                
                Log::info("Stock item deleted and aggregates updated: #{$model->id}");
            });
        });
    }

    static public function prepare($model)
    {
        $sub_category = StockSubCategory::find($model->stock_sub_category_id);
        if ($sub_category == null) {
            throw new \Exception("Invalid Stock Sub Category selected. Please choose a valid sub-category.");
        }
        $model->stock_category_id = $sub_category->stock_category_id;

        $user = User::find($model->created_by_id);
        if ($user == null) {
            throw new \Exception("Invalid User. User not found.");
        }
        $financial_period = Utils::getActiveFinancialPeriod($user->company_id);

        if ($financial_period == null) {
            throw new \Exception("No active Financial Period found. Please activate a financial period before creating stock items.");
        }
        $model->financial_period_id = $financial_period->id;
        $model->company_id = $user->company_id;

        // Validate prices
        if ($model->buying_price < 0) {
            throw new \Exception("Buying price cannot be negative.");
        }
        if ($model->selling_price < 0) {
            throw new \Exception("Selling price cannot be negative.");
        }
        if ($model->selling_price < $model->buying_price) {
            Log::warning("Stock item selling price is less than buying price. Item: {$model->name}");
        }

        // Validate quantities
        if ($model->original_quantity < 0) {
            throw new \Exception("Original quantity cannot be negative.");
        }

        // SKU generation
        if ($model->sku == null || strlen($model->sku) < 2) {
            $model->sku = Utils::generateSKU($model->stock_sub_category_id);
        }
        if ($model->update_sku == "Yes" && $model->generate_sku == 'Manual') {
            $model->sku = Utils::generateSKU($model->stock_sub_category_id);
            $model->update_sku = "No";
        }

        // Validate SKU uniqueness
        $existing_sku = StockItem::where('sku', $model->sku)
            ->where('company_id', $model->company_id)
            ->where('id', '!=', $model->id ?? 0)
            ->first();
        if ($existing_sku) {
            throw new \Exception("SKU '{$model->sku}' already exists for another item: {$existing_sku->name}. Please use a unique SKU.");
        }

        return $model;
    }


    //getter for gallery 
    public function getGalleryAttribute($value)
    {
        if ($value != null && strlen($value) > 3) {
            $d = json_decode($value); 
            if (is_array($d)) {
                return $d;
            }
        }
        return [];
    }

    //setter for gallery
    public function setGalleryAttribute($value)
    {
        $this->attributes['gallery'] = json_encode($value, true);
    }

    //appengs for name_text
    protected $appends = ['name_text'];

    //getter for name_text
    public function getNameTextAttribute()
    {
        $name_text = $this->name;
        if ($this->stockSubCategory != null) {
            $name_text =  $name_text . " - " . $this->stockSubCategory->name;
        }
        //add current quantity on name
        $name_text = $name_text . " (" . number_format($this->current_quantity) . " " . $this->stockSubCategory->measurement_unit . ")";
        return $name_text;
    }

    //stockSubCategory relation
    public function stockSubCategory()
    {
        return $this->belongsTo(StockSubCategory::class);
    }
}
