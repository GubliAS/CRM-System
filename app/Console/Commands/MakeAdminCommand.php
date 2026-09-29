<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'crm:make-admin {email : The user email to promote}';

    protected $description = 'Assign the Administrator role to a user by email';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error("No user found for {$email}.");

            return self::FAILURE;
        }

        $role = Role::query()->where('slug', 'admin')->first();

        if (! $role instanceof Role) {
            $this->error('Role admin was not found. Run php artisan db:seed --class=CrmSeeder first.');

            return self::FAILURE;
        }

        $user->forceFill(['role_id' => $role->id])->save();

        $this->info("{$user->email} is now Administrator.");

        return self::SUCCESS;
    }
}
