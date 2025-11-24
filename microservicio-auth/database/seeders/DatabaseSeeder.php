<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // ¡Asegúrate de importar Hash!

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. **Comenta o elimina** la línea que usa factory()
        // User::factory(10)->create(); 

        // 2. **Crea el usuario directamente** usando el método create()
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@empresa.com',
            'password' => Hash::make('password123'), // Hashea la contraseña
        ]);

        // Si tenías el usuario de prueba, puedes comentarlo o cambiarlo por el de admin:
        /*
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        */
    }
}