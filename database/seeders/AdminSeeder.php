<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed admin accounts for testing.
     *
     * Creates:
     * - 1 Super Admin (service provider level - can see all organizations)
     * - 1 Demo Organization with an Org Admin
     * - 2 Scout users in the demo organization
     */
    public function run(): void
    {
        // Create a demo organization
        $demoOrg = Organization::firstOrCreate(
            ['name' => 'Demo Scouting Agency'],
            [
                'subscription_tier' => 'professional',
                'advanced_analytics_enabled' => true,
            ]
        );

        $this->command->info("Created/found organization: {$demoOrg->name}");

        // Super Admin - Service Provider Level (no organization)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@basketballspy.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'organization_id' => null,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Super Admin: {$superAdmin->email} (password: password)");

        // Organization Admin
        $orgAdmin = User::firstOrCreate(
            ['email' => 'admin@demo-agency.com'],
            [
                'name' => 'Demo Org Admin',
                'password' => Hash::make('password'),
                'role' => 'org_admin',
                'organization_id' => $demoOrg->id,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Org Admin: {$orgAdmin->email} (password: password)");

        // Scout users
        $scout1 = User::firstOrCreate(
            ['email' => 'scout1@demo-agency.com'],
            [
                'name' => 'Marcus Johnson',
                'password' => Hash::make('password'),
                'role' => 'scout',
                'organization_id' => $demoOrg->id,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Scout: {$scout1->email} (password: password)");

        $scout2 = User::firstOrCreate(
            ['email' => 'scout2@demo-agency.com'],
            [
                'name' => 'Sarah Williams',
                'password' => Hash::make('password'),
                'role' => 'scout',
                'organization_id' => $demoOrg->id,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info("Scout: {$scout2->email} (password: password)");

        $this->command->newLine();
        $this->command->info('Admin seeding complete!');
        $this->command->table(
            ['Role', 'Email', 'Organization'],
            [
                ['Super Admin', 'superadmin@basketballspy.com', 'N/A (all access)'],
                ['Org Admin', 'admin@demo-agency.com', $demoOrg->name],
                ['Scout', 'scout1@demo-agency.com', $demoOrg->name],
                ['Scout', 'scout2@demo-agency.com', $demoOrg->name],
            ]
        );
    }
}
