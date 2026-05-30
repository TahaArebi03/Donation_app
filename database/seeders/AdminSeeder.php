<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'firstName' => 'Admin',
            'lastName' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('123456'),
            'role' => 'admin'
        ]);
        $user->admin()->create([
            // 'user_id'=>$user->id,  --- IGNORE ---
        ]);
        
    }
}
