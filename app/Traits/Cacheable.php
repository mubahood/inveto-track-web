<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Register cache-clearing observers when the model is first initialized.
     * This runs even if the model has its own boot() method.
     */
    public static function bootCacheable()
    {
        // Register model observers for cache clearing
        static::created(function ($model) {
            $model->clearModelCache();
        });

        static::updated(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Get cache key for this model.
     *
     * @param string $suffix
     * @return string
     */
    public function getCacheKey(string $suffix = ''): string
    {
        $modelName = class_basename($this);
        $companyId = $this->company_id ?? 'global';
        
        return $suffix 
            ? "{$modelName}:{$companyId}:{$suffix}"
            : "{$modelName}:{$companyId}:all";
    }

    /**
     * Get cache key for a specific company.
     *
     * @param int|null $companyId
     * @param string $suffix
     * @return string
     */
    public static function getCompanyCacheKey(?int $companyId = null, string $suffix = ''): string
    {
        $modelName = class_basename(static::class);
        $companyId = $companyId ?? 'global';
        
        return $suffix 
            ? "{$modelName}:{$companyId}:{$suffix}"
            : "{$modelName}:{$companyId}:all";
    }

    /**
     * Clear all cache for this model.
     */
    public function clearModelCache(): void
    {
        $modelName = class_basename($this);
        $companyId = $this->company_id ?? 'global';
        
        // Check if cache driver supports tagging
        if (method_exists(Cache::getStore(), 'tags')) {
            // Clear all cache keys for this model and company using tags
            Cache::tags(["{$modelName}:{$companyId}"])->flush();
            
            // Also clear global cache for this model
            Cache::tags([$modelName])->flush();
        } else {
            // Fallback: Clear specific keys when tags are not supported
            // These match the keys used in CacheService
            $patterns = [
                // Company keys
                "company:settings:{$companyId}",
                
                // Category keys
                "stock_categories:{$companyId}",
                "stock_sub_categories:{$companyId}:all",
                "budget_item_categories:{$companyId}",
                "financial_categories:{$companyId}",
                
                // Financial period keys
                "financial_periods:{$companyId}",
                "financial_period:active:{$companyId}",
                
                // Legacy pattern keys
                "{$modelName}:{$companyId}:all",
                "{$modelName}:all:{$companyId}",
            ];
            
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear cache for a specific company.
     *
     * @param int|null $companyId
     */
    public static function clearCompanyCache(?int $companyId = null): void
    {
        $modelName = class_basename(static::class);
        $companyId = $companyId ?? 'global';
        
        // Check if cache driver supports tagging
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(["{$modelName}:{$companyId}"])->flush();
            Cache::tags([$modelName])->flush();
        } else {
            // Fallback: Clear specific keys matching CacheService patterns
            $patterns = [
                "company:settings:{$companyId}",
                "stock_categories:{$companyId}",
                "stock_sub_categories:{$companyId}:all",
                "budget_item_categories:{$companyId}",
                "financial_categories:{$companyId}",
                "financial_periods:{$companyId}",
                "financial_period:active:{$companyId}",
                "{$modelName}:{$companyId}:all",
                "{$modelName}:all:{$companyId}",
            ];
            
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Get cached collection for this model.
     *
     * @param int|null $companyId
     * @param int $ttl Time to live in minutes (default 60)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCached(?int $companyId = null, int $ttl = 60)
    {
        $cacheKey = static::getCompanyCacheKey($companyId);
        
        return Cache::tags([class_basename(static::class)])->remember($cacheKey, $ttl * 60, function () use ($companyId) {
            $query = static::query();
            
            if ($companyId && in_array('company_id', (new static())->getFillable())) {
                $query->where('company_id', $companyId);
            }
            
            return $query->get();
        });
    }

    /**
     * Get cached model by ID.
     *
     * @param int $id
     * @param int $ttl Time to live in minutes (default 60)
     * @return mixed
     */
    public static function findCached(int $id, int $ttl = 60)
    {
        $modelName = class_basename(static::class);
        $cacheKey = "{$modelName}:{$id}";
        
        return Cache::tags([$modelName])->remember($cacheKey, $ttl * 60, function () use ($id) {
            return static::find($id);
        });
    }
}
