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
        Schema::create('recently_viewed_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['user_id', 'viewable_type', 'viewable_id'], 'recently_viewed_user_record_unique');
            $table->index(['user_id', 'viewable_type', 'viewed_at'], 'recently_viewed_user_type_viewed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_records');
    }
};
