<?php

namespace Database\Seeders;

use App\Actions\Reports\SeedPrebuiltReports;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->orderBy('id')->first();

        if ($owner === null) {
            return;
        }

        app(SeedPrebuiltReports::class)->handle($owner);
    }
}
