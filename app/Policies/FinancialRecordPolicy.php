<?php

namespace App\Policies;

use App\Models\FinancialRecord;
use App\Models\User;

class FinancialRecordPolicy extends BasePolicy
{
    /**
     * Determine if the user can create financial records.
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

        // Check worker permissions for financial records
        return $company->allow_worker_to_add_financial_records === 'Yes';
    }

    /**
     * Determine if the user can update the financial record.
     */
    public function update(User $user, $financialRecord): bool
    {
        // Must be from same company
        if ($user->company_id !== $financialRecord->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always update
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_edit_financial_records === 'Yes';
    }

    /**
     * Determine if the user can delete the financial record.
     */
    public function delete(User $user, $financialRecord): bool
    {
        // Must be from same company
        if ($user->company_id !== $financialRecord->company_id) {
            return false;
        }

        $company = $user->company;

        // Owner can always delete
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_delete_financial_records === 'Yes';
    }

    /**
     * Determine if the user can view financial reports.
     */
    public function viewReports(User $user): bool
    {
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Everyone in company can view reports
        return true;
    }

    /**
     * Determine if the user can manage financial periods.
     */
    public function manageFinancialPeriods(User $user): bool
    {
        $company = $user->company;
        
        if (!$company) {
            return false;
        }

        // Only owner can manage financial periods
        return $company->owner == $user->id;
    }
}
