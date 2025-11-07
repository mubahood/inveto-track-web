<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // User who performed the action
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Model information
            $table->string('model_type'); // e.g., App\Models\StockItem
            $table->unsignedBigInteger('model_id'); // ID of the affected model
            
            // Action type: created, updated, deleted
            $table->enum('action', ['created', 'updated', 'deleted'])->index();
            
            // Data changes (JSON format)
            $table->json('old_values')->nullable(); // Values before change
            $table->json('new_values')->nullable(); // Values after change
            
            // Request context
            $table->string('ip_address', 45)->nullable(); // Support IPv4 and IPv6
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            
            // Company for multi-tenancy
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['model_type', 'model_id']);
            $table->index('company_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
