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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->foreignId('assigned_to_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related');
            $table->foreignId('contact_id')->nullable()->index()->constrained('contacts')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('show_time_as', 20)->default('Busy');
            $table->boolean('is_private')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
