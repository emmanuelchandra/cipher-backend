<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::create(['name' => 'Management']);

        User::create([
            'name' => 'Admin CIPHER',
            'email' => 'admin@cipher.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department_id' => $dept->id,
        ]);

        User::create([
            'name' => 'HR Manager',
            'email' => 'hr@cipher.com',
            'password' => Hash::make('password'),
            'role' => 'hr',
            'department_id' => $dept->id,
        ]);

        Shift::create([
            'name' => 'Morning',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        Shift::create([
            'name' => 'Evening',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
        ]);

        CompanySetting::create([
            'company_name' => 'CIPHER Corp',
            'company_address' => '123 Main Street, Jakarta, Indonesia',
        ]);
    }
}
