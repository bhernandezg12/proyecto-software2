<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin Usuario',
            'email' => 'admin@empresa.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'active' => true,
            'phone' => '+57-300-123-4567',
            'department' => 'Administración'
        ]);

        User::create([
            'name' => 'Usuario Regular',
            'email' => 'user@empresa.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'active' => true,
            'phone' => '+57-301-987-6543',
            'department' => 'Ventas'
        ]);

        User::create([
            'name' => 'Manager',
            'email' => 'manager@empresa.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'active' => true,
            'phone' => '+57-302-555-6789',
            'department' => 'Operaciones'
        ]);
    }
}
