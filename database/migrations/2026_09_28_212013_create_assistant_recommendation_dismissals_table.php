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
        Schema::create('assistant_recommendation_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rule', 40);
            $table->string('recommendable_type');
            $table->unsignedBigInteger('recommendable_id');
            $table->timestamps();

            $table->unique(
                ['user_id', 'rule', 'recommendable_type', 'recommendable_id'],
                'assistant_dismissals_user_rule_record_unique',
            );
            $table->index(['recommendable_type', 'recommendable_id'], 'assistant_dismissals_recommendable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_recommendation_dismissals');
    }
};
