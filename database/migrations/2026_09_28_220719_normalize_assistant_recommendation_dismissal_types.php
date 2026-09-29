<?php

use App\Models\Account;
use App\Models\Opportunity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('assistant_recommendation_dismissals')
            ->where('recommendable_type', 'account')
            ->update(['recommendable_type' => Account::class]);

        DB::table('assistant_recommendation_dismissals')
            ->where('recommendable_type', 'opportunity')
            ->update(['recommendable_type' => Opportunity::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('assistant_recommendation_dismissals')
            ->where('recommendable_type', Account::class)
            ->update(['recommendable_type' => 'account']);

        DB::table('assistant_recommendation_dismissals')
            ->where('recommendable_type', Opportunity::class)
            ->update(['recommendable_type' => 'opportunity']);
    }
};
