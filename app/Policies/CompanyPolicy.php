<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy extends BasePolicy
{
    /**
     * Determine if the user can view any companies.
     * Super admins can view all, others only their own.
     */
    public function viewAny(User $user): bool
    {
        return true; // All users can view their company
    }

    /**
     * Determine if the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        // Super admin or same company
        return $user->user_type === 'admin' || $user->company_id === $company->id;
    }

    /**
     * Determine if the user can create companies.
     * Only super admins can create companies.
     */
    public function create(User $user): bool
    {
        return $user->user_type === 'admin';
    }

    /**
     * Determine if the user can update the company.
     * Only company owner or super admin.
     */
    public function update(User $user, Company $company): bool
    {
        if ($user->user_type === 'admin') {
            return true;
        }

        // Check if user is the company owner
        return $user->company_id === $company->id && $company->owner == $user->id;
    }

    /**
     * Determine if the user can delete the company.
     * Only super admins can delete companies.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->user_type === 'admin';
    }

    /**
     * Determine if user can manage company settings.
     */
    public function manageSettings(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $company->owner == $user->id;
    }

    /**
     * Determine if user can manage company workers/employees.
     */
    public function manageWorkers(User $user, Company $company): bool
    {
        if ($user->company_id !== $company->id) {
            return false;
        }

        // Owner can always manage
        if ($company->owner == $user->id) {
            return true;
        }

        // Check worker permissions
        return $company->allow_worker_to_add_worker === 'Yes';
    }
}
