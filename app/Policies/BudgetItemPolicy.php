<?php

namespace App\Policies;

use App\Models\BudgetItem;
use App\Models\User;

class BudgetItemPolicy extends BasePolicy
{
    /**
     * Determine if the user can create budget items.
     */
    public function create(User $user): bool
    {
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Owner can always create
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions for budget management
        return $company->allow_worker_to_add_budget === 'Yes';
    }

    /**
     * Determine if the user can update the budget item.
     */
    public function update(User $user, $budgetItem): bool
    {
        // Must be from same company
        if ($user->company_id !== $budgetItem->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always update
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_edit_budget === 'Yes';
    }

    /**
     * Determine if the user can delete the budget item.
     */
    public function delete(User $user, $budgetItem): bool
    {
        // Must be from same company
        if ($user->company_id !== $budgetItem->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always delete
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_delete_budget === 'Yes';
    }

    /**
     * Determine if the user can manage budget programs.
     */
    public function manageBudgetPrograms(User $user): bool
    {
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Owner can always manage
        if ($company->owner == $user->id) {
            return true;
        }

        return $company->allow_worker_to_add_budget === 'Yes';
    }
}
