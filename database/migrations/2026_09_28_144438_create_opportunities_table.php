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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->foreignId('account_id')->index()->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('close_date');
            $table->string('stage', 40);
            $table->unsignedTinyInteger('probability')->default(10);
            $table->string('type', 40)->nullable();
            $table->string('lead_source', 40)->nullable();
            $table->string('next_step')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_won')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
