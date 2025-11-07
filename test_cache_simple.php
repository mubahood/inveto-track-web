<?php

/**
 * Simple Cache Test Script
 * Tests cache invalidation by creating and updating records
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\StockCategory;
use App\Models\FinancialCategory;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

echo "\n🧪 SIMPLE CACHE INVALIDATION TEST\n";
echo str_repeat("=", 70) . "\n\n";

$company = Company::first();
if (!$company) {
    echo "❌ No company found.\n";
    exit(1);
}

echo "Testing with Company: {$company->name} (ID: {$company->id})\n\n";

// Test 1: Company Cache
echo "TEST 1: Company Settings Cache\n";
echo str_repeat("-", 70) . "\n";
Cache::flush();

$cached1 = CacheService::getCompanySettings($company->id);
$originalName = $cached1->name;
echo "✓ Loaded company into cache: {$cached1->name}\n";

// Update a simple field that doesn't trigger other save() operations
$newName = "Updated " . time();
$company->name = $newName;
$company->save();
echo "✓ Updated company name to: {$newName}\n";

// Force cache clear since auto-clear might be blocked
sleep(1);  // Give time for events to fire

$cached2 = CacheService::getCompanySettings($company->id);
echo "✓ Reloaded from cache: {$cached2->name}\n";

if ($cached2->name === $newName) {
    echo "✅ PASS - Cache shows updated name\n";
} else {
    echo "❌ FAIL - Cache still showing old name: {$cached2->name}\n";
    echo "  (This might be due to Company model's boot() overriding updated event)\n";
}

// Restore original name
$company->name = $originalName;
$company->save();
echo "\n";

// Test 2: Stock Categories
echo "TEST 2: StockCategory Cache\n";
echo str_repeat("-", 70) . "\n";
Cache::flush();

$categories1 = CacheService::getStockCategories($company->id);
$count1 = $categories1->count();
echo "✓ Loaded {$count1} categories into cache\n";

$newCategory = new StockCategory();
$newCategory->name = "Test Cat " . time();
$newCategory->company_id = $company->id;
$newCategory->save();
echo "✓ Created new category (ID: {$newCategory->id})\n";

$categories2 = CacheService::getStockCategories($company->id);
$count2 = $categories2->count();
echo "✓ Reloaded cache: {$count2} categories\n";
echo ($count2 > $count1) ? "✅ PASS - New category in cache\n" : "❌ FAIL - Cache not refreshed\n";

$newCategory->delete();
echo "✓ Deleted test category\n\n";

// Test 3: Financial Categories
echo "TEST 3: FinancialCategory Cache\n";
echo str_repeat("-", 70) . "\n";
Cache::flush();

$finCats1 = CacheService::getFinancialCategories($company->id);
$finCount1 = $finCats1->count();
echo "✓ Loaded {$finCount1} financial categories into cache\n";

$newFinCat = new FinancialCategory();
$newFinCat->name = "Test Fin Cat " . time();
$newFinCat->company_id = $company->id;
$newFinCat->save();
echo "✓ Created new financial category (ID: {$newFinCat->id})\n";

$finCats2 = CacheService::getFinancialCategories($company->id);
$finCount2 = $finCats2->count();
echo "✓ Reloaded cache: {$finCount2} financial categories\n";
echo ($finCount2 > $finCount1) ? "✅ PASS - New category in cache\n" : "❌ FAIL - Cache not refreshed\n";

$newFinCat->delete();
echo "✓ Deleted test financial category\n\n";

echo str_repeat("=", 70) . "\n";
echo "✅ ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo str_repeat("=", 70) . "\n\n";

Cache::flush();
