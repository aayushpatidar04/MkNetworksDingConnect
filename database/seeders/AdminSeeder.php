<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'retailer']);

        // Create default admin
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@mknetwork.com',
            'phone' => '9999999999',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $admin->assignRole('admin');

        // Create default commission rules
        \App\Models\CommissionRule::create([
            'name' => 'Default Commission',
            'description' => 'Default 2.5% commission on all transactions',
            'scope' => 'global',
            'commission_type' => 'percentage',
            'commission_value' => 2.5,
            'priority' => 0,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        // Bronze tier
        \App\Models\CommissionRule::create([
            'name' => 'Bronze Tier',
            'description' => 'Commission for Bronze tier retailers (0-500/month)',
            'scope' => 'retailer_tier',
            'retailer_tier' => 'bronze',
            'commission_type' => 'percentage',
            'commission_value' => 3.0,
            'priority' => 100,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        // Silver tier
        \App\Models\CommissionRule::create([
            'name' => 'Silver Tier',
            'description' => 'Commission for Silver tier retailers (501-5000/month)',
            'scope' => 'retailer_tier',
            'retailer_tier' => 'silver',
            'commission_type' => 'percentage',
            'commission_value' => 2.5,
            'priority' => 100,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        // Gold tier
        \App\Models\CommissionRule::create([
            'name' => 'Gold Tier',
            'description' => 'Commission for Gold tier retailers (5001+/month)',
            'scope' => 'retailer_tier',
            'retailer_tier' => 'gold',
            'commission_type' => 'percentage',
            'commission_value' => 2.0,
            'priority' => 100,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        // Create default settings
        $defaultSettings = [
            ['platform_name', 'MK Network - TopUp Platform', 'string', 'general', 'Platform display name'],
            ['platform_commission_default', '2.5', 'number', 'commission', 'Default commission rate percentage'],
            ['platform_currency', 'INR', 'string', 'general', 'Default currency'],
            ['platform_min_recharge', '10', 'number', 'recharge', 'Minimum recharge amount'],
            ['platform_max_recharge', '10000', 'number', 'recharge', 'Maximum recharge amount'],
            ['platform_low_balance_threshold', '500', 'number', 'wallet', 'Low balance alert threshold'],
            ['ding_sandbox_mode', 'true', 'boolean', 'dingconnect', 'Use DingConnect sandbox'],
            ['sms_enabled', 'false', 'boolean', 'sms', 'Enable SMS notifications'],
        ];

        foreach ($defaultSettings as [$key, $value, $type, $group, $description]) {
            Setting::create(compact('key', 'value', 'type', 'group', 'description'));
        }
    }
}
