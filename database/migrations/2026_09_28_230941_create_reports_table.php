<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('folder', 32);
            $table->string('object_type', 32);
            $table->json('columns');
            $table->json('filters')->nullable();
            $table->json('group_by')->nullable();
            $table->json('chart')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('system_key')->nullable()->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('folder');
            $table->index('object_type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
