<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_records', function (Blueprint $table) {
            // Add date field if it doesn't exist
            if (!Schema::hasColumn('stock_records', 'date')) {
                $table->date('date')->nullable()->after('profit');
            }
        });
        
        // Set date to created_at date for existing records without a date
        DB::statement('UPDATE stock_records SET date = DATE(created_at) WHERE date IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_records', function (Blueprint $table) {
            if (Schema::hasColumn('stock_records', 'date')) {
                $table->dropColumn('date');
            }
        });
    }
};
