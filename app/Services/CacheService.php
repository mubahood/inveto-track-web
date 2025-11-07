<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StockCategory;
use App\Models\StockSubCategory;
use App\Models\BudgetItemCategory;
use App\Models\FinancialCategory;
use App\Models\FinancialPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class CacheService
{
    /**
     * Default cache TTL in minutes.
     */
    const DEFAULT_TTL = 60; // 1 hour
    
    /**
     * Cache TTL for rarely changing data (in minutes).
     */
    const LONG_TTL = 1440; // 24 hours
    
    /**
     * Cache TTL for frequently changing data (in minutes).
     */
    const SHORT_TTL = 10; // 10 minutes

    /**
     * Get company settings with caching.
     *
     * @param int|null $companyId
     * @return Company|null
     */
    public static function getCompanySettings(?int $companyId = null): ?Company
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if (!$companyId) {
            return null;
        }

        $cacheKey = "company:settings:{$companyId}";
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL * 60, function () use ($companyId) {
            return Company::find($companyId);
        });
    }

    /**
     * Clear company settings cache.
     *
     * @param int|null $companyId
     */
    public static function clearCompanySettings(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if ($companyId) {
            Cache::forget("company:settings:{$companyId}");
        }
    }

    /**
     * Get stock categories with caching.
     *
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getStockCategories(?int $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = "stock_categories:{$companyId}";
        
        return Cache::remember($cacheKey, self::LONG_TTL * 60, function () use ($companyId) {
            return StockCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get stock sub-categories with caching.
     *
     * @param int|null $companyId
     * @param int|null $categoryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getStockSubCategories(?int $companyId = null, ?int $categoryId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = $categoryId 
            ? "stock_sub_categories:{$companyId}:{$categoryId}"
            : "stock_sub_categories:{$companyId}:all";
        
        return Cache::remember($cacheKey, self::LONG_TTL * 60, function () use ($companyId, $categoryId) {
            $query = StockSubCategory::where('company_id', $companyId);
            
            if ($categoryId) {
                $query->where('stock_category_id', $categoryId);
            }
            
            return $query->orderBy('name')->get();
        });
    }

    /**
     * Get budget item categories with caching.
     *
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBudgetItemCategories(?int $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = "budget_item_categories:{$companyId}";
        
        return Cache::remember($cacheKey, self::LONG_TTL * 60, function () use ($companyId) {
            return BudgetItemCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get financial categories with caching.
     *
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFinancialCategories(?int $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = "financial_categories:{$companyId}";
        
        return Cache::remember($cacheKey, self::LONG_TTL * 60, function () use ($companyId) {
            return FinancialCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get financial periods with caching.
     *
     * @param int|null $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFinancialPeriods(?int $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = "financial_periods:{$companyId}";
        
        return Cache::remember($cacheKey, self::SHORT_TTL * 60, function () use ($companyId) {
            return FinancialPeriod::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get active financial period with caching.
     *
     * @param int|null $companyId
     * @return FinancialPeriod|null
     */
    public static function getActiveFinancialPeriod(?int $companyId = null): ?FinancialPeriod
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        $cacheKey = "financial_period:active:{$companyId}";
        
        return Cache::remember($cacheKey, self::SHORT_TTL * 60, function () use ($companyId) {
            return FinancialPeriod::where('company_id', $companyId)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Clear all category caches for a company.
     *
     * @param int|null $companyId
     */
    public static function clearCategoryCaches(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if ($companyId) {
            Cache::forget("stock_categories:{$companyId}");
            Cache::forget("stock_sub_categories:{$companyId}:all");
            Cache::forget("budget_item_categories:{$companyId}");
            Cache::forget("financial_categories:{$companyId}");
        }
    }

    /**
     * Clear financial period caches for a company.
     *
     * @param int|null $companyId
     */
    public static function clearFinancialPeriodCaches(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if ($companyId) {
            Cache::forget("financial_periods:{$companyId}");
            Cache::forget("financial_period:active:{$companyId}");
        }
    }

    /**
     * Clear all caches for a company.
     *
     * @param int|null $companyId
     */
    public static function clearAllCompanyCaches(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if ($companyId) {
            self::clearCompanySettings($companyId);
            self::clearCategoryCaches($companyId);
            self::clearFinancialPeriodCaches($companyId);
            
            // Clear any wildcard caches
            Cache::flush();
        }
    }

    /**
     * Warm up caches for a company (preload frequently accessed data).
     *
     * @param int|null $companyId
     */
    public static function warmUpCaches(?int $companyId = null): void
    {
        $companyId = $companyId ?? Auth::user()?->company_id;
        
        if ($companyId) {
            self::getCompanySettings($companyId);
            self::getStockCategories($companyId);
            self::getStockSubCategories($companyId);
            self::getBudgetItemCategories($companyId);
            self::getFinancialCategories($companyId);
            self::getFinancialPeriods($companyId);
            self::getActiveFinancialPeriod($companyId);
        }
    }
}
