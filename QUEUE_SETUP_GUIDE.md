# Queue Worker Setup Guide

## 🚀 Quick Start

The application now uses queued jobs for email notifications. You **must** run queue workers for emails to be sent.

## Development Environment

### Start Queue Worker (Keep this running)
```bash
php artisan queue:work
```

### Alternative: Process Queue Once
```bash
php artisan queue:work --once
```

## Configuration

### 1. Update `.env` file
```env
QUEUE_CONNECTION=database
```

### 2. Create jobs table (if not exists)
```bash
php artisan queue:table
php artisan migrate
```

### 3. Test Queue
```bash
# Dispatch a test job
php artisan tinker
>>> \App\Jobs\SendBudgetItemUpdateEmail::dispatch(\App\Models\BudgetItem::first());
>>> exit

# Check the queue
php artisan queue:work --once
```

## Production Environment

### Using Supervisor (Recommended)

1. **Install Supervisor**
```bash
sudo apt-get install supervisor
```

2. **Create config file:** `/etc/supervisor/conf.d/inveto-track-worker.conf`
```ini
[program:inveto-track-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/inveto-track-web/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/inveto-track-web/storage/logs/worker.log
stopwaitsecs=3600
```

3. **Start Supervisor**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start inveto-track-worker:*
```

4. **Check status**
```bash
sudo supervisorctl status inveto-track-worker:*
```

### Restarting Workers After Code Changes
```bash
php artisan queue:restart
```

## Monitoring

### View Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

### Clear Failed Jobs
```bash
php artisan queue:flush
```

## Troubleshooting

### Queue Not Processing
1. Check if worker is running: `ps aux | grep "queue:work"`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Check failed jobs table: `php artisan queue:failed`

### Email Not Sending
1. Verify queue worker is running
2. Check mail configuration in `.env`
3. Check logs for errors

### High Memory Usage
- Restart workers periodically: `php artisan queue:restart`
- Use `--max-time` and `--memory` options

## Alternative Queue Drivers

### Redis (Better Performance)
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Amazon SQS (Scalable)
```env
QUEUE_CONNECTION=sqs
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
SQS_QUEUE=your-queue-name
```

## What's Queued Now

- ✅ **Budget Item Update Emails** - `SendBudgetItemUpdateEmail` job
  - Triggered when budget items are created/updated
  - Sends to all company users + notification email

## Next Steps

Additional jobs to create:
- [ ] Report generation (PDF exports)
- [ ] Stock level alerts
- [ ] Financial period aggregation updates
- [ ] Backup operations

---

**Important:** Always keep at least one queue worker running in production!
