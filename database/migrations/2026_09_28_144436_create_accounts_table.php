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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->foreignId('parent_account_id')->nullable()->index()->constrained('accounts')->nullOnDelete();
            $table->string('phone', 40)->nullable();
            $table->string('fax', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('type', 40)->nullable();
            $table->string('industry', 80)->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_city', 80)->nullable();
            $table->string('billing_state', 80)->nullable();
            $table->string('billing_postal_code', 80)->nullable();
            $table->string('billing_country', 80)->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_city', 80)->nullable();
            $table->string('shipping_state', 80)->nullable();
            $table->string('shipping_postal_code', 80)->nullable();
            $table->string('shipping_country', 80)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
