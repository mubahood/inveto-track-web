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
        Schema::table('budget_programs', function (Blueprint $table) {
            $table->date('deadline')->nullable();
            $table->text('rsvp')->nullable();
            $table->text('logo')->nullable();
            $table->string('is_default')->nullable();
            $table->string('is_active')->nullable();
            $table->longText('groups')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_programs', function (Blueprint $table) {
            //
        });
    }
};
