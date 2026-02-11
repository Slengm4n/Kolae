<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Verifica se o email já existe antes de criar para não dar erro
        if(!User::where('email', 'admin@kolae.com')->exists()){
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@kolae.com',
                'password'=> Hash::make('adminkolae77'),
                'role' => 'admin',
                'status' => 'active',
                'birthdate' => '2024-01-06',
                'force_password_change' => false,
            ]);
            $this->command->info('Admin user created successfully!');
        }else{
            $this->command->warn('Admin already exists.');
        }
    }
}
