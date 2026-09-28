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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('case_number', 20)->nullable()->unique();
            $table->foreignId('contact_id')->nullable()->index()->constrained('contacts')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->index()->constrained('accounts')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->text('internal_comments')->nullable();
            $table->string('status', 40)->default('New');
            $table->string('priority', 20)->nullable();
            $table->string('type', 40)->nullable();
            $table->string('origin', 40);
            $table->string('reason', 80)->nullable();
            $table->string('web_email', 80)->nullable();
            $table->string('web_name', 80)->nullable();
            $table->string('web_company')->nullable();
            $table->string('web_phone', 40)->nullable();
            $table->boolean('is_closed')->default(false);
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
