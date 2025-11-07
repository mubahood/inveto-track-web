<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

trait AuditLogger
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditLogger()
    {
        // Log model creation
        static::created(function ($model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        // Log model updates
        static::updated(function ($model) {
            $model->logAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        // Log model deletion
        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Log an audit entry.
     *
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return void
     */
    protected function logAudit(string $action, ?array $oldValues, ?array $newValues)
    {
        // Skip logging if we're in a testing environment or console without user
        if (!Auth::check() && !app()->runningInConsole()) {
            return;
        }

        // Get user ID - verify it exists in database
        $userId = Auth::id();
        
        // Verify user exists to prevent foreign key constraint errors
        if ($userId) {
            $userExists = DB::table('users')->where('id', $userId)->exists();
            if (!$userExists) {
                // User doesn't exist in database, set to null
                $userId = null;
            }
        }

        // Get company ID if the model has it
        $companyId = null;
        if (isset($this->company_id)) {
            $companyId = $this->company_id;
        } elseif (Auth::check() && Auth::user()->company_id) {
            $companyId = Auth::user()->company_id;
        }

        // Filter out sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        if ($oldValues) {
            $oldValues = $this->filterSensitiveData($oldValues, $sensitiveFields);
        }
        if ($newValues) {
            $newValues = $this->filterSensitiveData($newValues, $sensitiveFields);
        }

        // Create audit log entry
        AuditLog::create([
            'user_id' => $userId, // Will be null if user doesn't exist
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'url' => Request::fullUrl(),
            'company_id' => $companyId,
        ]);
    }

    /**
     * Filter out sensitive data from audit logs.
     *
     * @param array $data
     * @param array $sensitiveFields
     * @return array
     */
    protected function filterSensitiveData(array $data, array $sensitiveFields): array
    {
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }
        return $data;
    }
}
