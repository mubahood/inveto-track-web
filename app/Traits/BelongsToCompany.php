<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BelongsToCompany
 * 
 * Automatically scopes queries to the authenticated user's company.
 * This ensures multi-tenant data isolation in a SaaS application.
 * 
 * Usage:
 * - Add this trait to any model that has a company_id column
 * - Queries will automatically filter by the authenticated user's company_id
 * - To bypass the scope (e.g., for admin operations), use: Model::withoutGlobalScope('company')
 * 
 * @package App\Traits
 */
trait BelongsToCompany
{
    /**
     * Boot the trait.
     * Adds a global scope that filters all queries by company_id.
     */
    protected static function bootBelongsToCompany()
    {
        // Only apply scope when user is authenticated
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });

        // Automatically set company_id when creating new records
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->company_id) {
                if (!$model->company_id) {
                    $model->company_id = auth()->user()->company_id;
                }
            }
        });
    }

    /**
     * Relationship to Company model.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
}
