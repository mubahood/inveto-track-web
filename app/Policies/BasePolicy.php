<?php

namespace App\Policies;

use App\Models\User;

/**
 * Base Policy Class
 * 
 * Provides common authorization logic for all policies.
 * All model-specific policies should extend this class.
 */
abstract class BasePolicy
{
    /**
     * Determine if the user can view any models.
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view records from their company
        return $user !== null;
    }

    /**
     * Determine if the user can view the model.
     * 
     * @param \App\Models\User $user
     * @param mixed $model
     * @return bool
     */
    public function view(User $user, $model): bool
    {
        // Users can only view records from their own company
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine if the user can create models.
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // All authenticated users can create records
        return $user !== null;
    }

    /**
     * Determine if the user can update the model.
     * 
     * @param \App\Models\User $user
     * @param mixed $model
     * @return bool
     */
    public function update(User $user, $model): bool
    {
        // Users can only update records from their own company
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine if the user can delete the model.
     * 
     * @param \App\Models\User $user
     * @param mixed $model
     * @return bool
     */
    public function delete(User $user, $model): bool
    {
        // Users can only delete records from their own company
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine if the user can restore the model.
     * 
     * @param \App\Models\User $user
     * @param mixed $model
     * @return bool
     */
    public function restore(User $user, $model): bool
    {
        // Users can only restore records from their own company
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine if the user can permanently delete the model.
     * 
     * @param \App\Models\User $user
     * @param mixed $model
     * @return bool
     */
    public function forceDelete(User $user, $model): bool
    {
        // Only super admins can force delete
        return $user->user_type === 'admin' && $user->company_id === $model->company_id;
    }

    /**
     * Check if user is an admin.
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isAdmin(User $user): bool
    {
        return $user->user_type === 'admin';
    }

    /**
     * Check if user is a company owner.
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isCompanyOwner(User $user): bool
    {
        return $user->company !== null && $user->company->owner === $user->id;
    }
}
