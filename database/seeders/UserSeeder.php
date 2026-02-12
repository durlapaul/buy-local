<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $owner1 = User::create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => '+40721000001',
            'password' => Hash::make('password123'),
        ]);
        $owner1->assignRole('consumer');

        $owner2 = User::create([
            'name' => 'Maria Garcia',
            'email' => 'maria@example.com',
            'phone' => '+40721000002',
            'password' => Hash::make('password123'),
        ]);
        $owner2->assignRole('consumer');

        $owner3 = User::create([
            'name' => 'David Chen',
            'email' => 'david@example.com',
            'phone' => '+40721000003',
            'password' => Hash::make('password123'),
        ]);
        $owner3->assignRole('consumer');

        // Create Space Workers (no subscription)
        $worker1 = User::create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah@example.com',
            'phone' => '+40721000004',
            'password' => Hash::make('password123'),
        ]);
        $worker1->assignRole('consumer');

        $worker2 = User::create([
            'name' => 'Mike Brown',
            'email' => 'mike@example.com',
            'phone' => '+40721000005',
            'password' => Hash::make('password123'),
        ]);
        $worker2->assignRole('consumer');

        $worker3 = User::create([
            'name' => 'Ana Popescu',
            'email' => 'ana@example.com',
            'phone' => '+40721000006',
            'password' => Hash::make('password123'),
        ]);
        $worker3->assignRole('consumer');

        // Create Regular Consumers
        $consumer1 = User::create([
            'name' => 'Alex Martinez',
            'email' => 'alex@example.com',
            'phone' => '+40721000007',
            'password' => Hash::make('password123'),
        ]);
        $consumer1->assignRole('consumer');

        $consumer2 = User::create([
            'name' => 'Emily White',
            'email' => 'emily@example.com',
            'phone' => '+40721000008',
            'password' => Hash::make('password123'),
        ]);
        $consumer2->assignRole('consumer');

        $consumer3 = User::create([
            'name' => 'James Wilson',
            'email' => 'james@example.com',
            'phone' => '+40721000009',
            'password' => Hash::make('password123'),
        ]);
        $consumer3->assignRole('consumer');

        $consumer4 = User::create([
            'name' => 'Laura Taylor',
            'email' => 'laura@example.com',
            'phone' => '+40721000010',
            'password' => Hash::make('password123'),
        ]);
        $consumer4->assignRole('consumer');
    }
}
