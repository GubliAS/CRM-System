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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->foreignId('assigned_to_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related');
            $table->foreignId('contact_id')->nullable()->index()->constrained('contacts')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('status', 40)->default('Not Started');
            $table->string('priority', 20)->nullable();
            $table->text('comments')->nullable();
            $table->boolean('reminder_set')->default(false);
            $table->dateTime('reminder_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
