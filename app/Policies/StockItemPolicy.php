<?php

namespace App\Policies;

use App\Models\StockItem;
use App\Models\User;

class StockItemPolicy extends BasePolicy
{
    /**
     * Determine if the user can create stock items.
     */
    public function create(User $user): bool
    {
        // Check company permissions
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Owner can always create
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_add_stock_items === 'Yes';
    }

    /**
     * Determine if the user can update the stock item.
     */
    public function update(User $user, $stockItem): bool
    {
        // Must be from same company
        if ($user->company_id !== $stockItem->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always update
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_edit_stock_items === 'Yes';
    }

    /**
     * Determine if the user can delete the stock item.
     */
    public function delete(User $user, $stockItem): bool
    {
        // Must be from same company
        if ($user->company_id !== $stockItem->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always delete
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_delete_stock_items === 'Yes';
    }

    /**
     * Determine if the user can manage stock categories.
     */
    public function manageCategories(User $user): bool
    {
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Owner can always manage
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_add_stock_categories === 'Yes';
    }
}
