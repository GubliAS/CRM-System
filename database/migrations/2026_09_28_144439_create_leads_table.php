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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('salutation', 20)->nullable();
            $table->string('first_name', 40)->nullable();
            $table->string('last_name', 80);
            $table->string('company');
            $table->string('title', 128)->nullable();
            $table->string('email', 80)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('mobile', 40)->nullable();
            $table->string('lead_status', 40)->default('New');
            $table->string('lead_source', 40)->nullable();
            $table->string('rating', 20)->nullable();
            $table->string('industry', 80)->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->unsignedInteger('number_of_employees')->nullable();
            $table->string('website')->nullable();
            $table->string('street')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('postal_code', 80)->nullable();
            $table->string('country', 80)->nullable();
            $table->text('description')->nullable();
            $table->boolean('converted')->default(false);
            $table->foreignId('converted_account_id')->nullable()->index()->constrained('accounts')->nullOnDelete();
            $table->foreignId('converted_contact_id')->nullable()->index()->constrained('contacts')->nullOnDelete();
            $table->foreignId('converted_opportunity_id')->nullable()->index()->constrained('opportunities')->nullOnDelete();
            $table->timestamps();

            $table->index('lead_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
