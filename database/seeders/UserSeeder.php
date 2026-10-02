<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Demo accounts per the role access matrix:
         * - Owner:      Full system access, add/edit staff accounts, view audit logs, access all reports.
         * - Manager:    Full access: inventory, consumption matrix, sales & inventory analytics, low stock alerts, approve refunds.
         * - Cashier:    POS order terminal, select sizes/add-ons, apply discounts, accept cash/online payment, print receipts.
         */
        $users = [
            [
                'name' => 'Admin Owner',
                'email' => 'owner@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'manager@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'status' => 'active',
            ],
            [
                // Shared POS / Cashier login
                'name' => 'POS Cashier',
                'email' => 'cashier@coffee.com',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'status' => 'active',
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
