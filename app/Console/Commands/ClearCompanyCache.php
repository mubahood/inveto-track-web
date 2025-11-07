<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearCompanyCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-company {company_id? : The ID of the company} {--all : Clear all company caches} {--warmup : Warm up caches after clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cached data for a specific company or all companies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->info('Clearing all company caches...');
            \Illuminate\Support\Facades\Cache::flush();
            $this->info('✓ All caches cleared successfully!');
            
            if ($this->option('warmup')) {
                $this->info('Cache warmup is not available for all companies option.');
            }
            
            return Command::SUCCESS;
        }

        $companyId = $this->argument('company_id');
        
        if (!$companyId) {
            $this->error('Please provide a company ID or use --all flag');
            return Command::FAILURE;
        }

        $this->info("Clearing caches for company ID: {$companyId}");
        
        CacheService::clearAllCompanyCaches($companyId);
        
        $this->info('✓ Company caches cleared successfully!');
        
        if ($this->option('warmup')) {
            $this->info('Warming up caches...');
            CacheService::warmUpCaches($companyId);
            $this->info('✓ Caches warmed up successfully!');
        }
        
        return Command::SUCCESS;
    }
}
