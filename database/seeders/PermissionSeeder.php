<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for ticket tiers
        $permissions = [
            'view-ticket-tiers',
            'create-ticket-tiers',
            'update-ticket-tiers',
            'delete-ticket-tiers',
            'publish-ticket-tiers',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create a test user with all permissions for development/testing
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Give all permissions to test user
        $user->givePermissionTo($permissions);

        $this->command->info('Permissions created and assigned to test user (test@example.com)');
    }
}
