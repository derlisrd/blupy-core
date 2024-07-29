<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $pass = env('PASS_ADMIN');
        User::factory()->create([
            'name' => 'Blupy Admin',
            'email' => 'admin@blupy.com.py',
            'password'=> Hash::make($pass),
            'rol'=>1
        ]);
    }
}
