<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class AssignDefaultRolesCommand extends Command
{
    protected $signature = 'crm:assign-default-roles';

    protected $description = 'Assign Sales Representative to users that have no role';

    public function handle(): int
    {
        $role = Role::query()->where('slug', 'sales-rep')->first();

        if (! $role instanceof Role) {
            $this->error('Role sales-rep was not found. Run php artisan db:seed --class=CrmSeeder first.');

            return self::FAILURE;
        }

        $updated = User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $role->id]);

        $this->info("Assigned sales-rep to {$updated} user(s).");

        return self::SUCCESS;
    }
}
