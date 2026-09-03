<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\School;
use App\Models\Subject;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Admin User
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@schoolresults.com',
                'password' => Hash::make('admin123'),
                'role' => 'Admin',
                'school_name' => 'All Schools',
            ]
        );

        // Default Leader User
        User::updateOrCreate(
            ['username' => 'leader1'],
            [
                'email' => 'leader@schoolresults.com',
                'password' => Hash::make('leader123'),
                'role' => 'Leader',
                'school_name' => 'St. Marys Secondary',
            ]
        );

        // Default Teacher User
        User::updateOrCreate(
            ['username' => 'teacher1'],
            [
                'email' => 'teacher@schoolresults.com',
                'password' => Hash::make('teacher123'),
                'role' => 'Teacher',
                'school_name' => 'St. Marys Secondary',
            ]
        );

        // Seed Default School
        School::updateOrCreate(
            ['school_name' => 'St. Marys Secondary'],
            ['address' => 'Dar es Salaam, Tanzania']
        );

        // Seed Default Subjects
        $subjects = ['Mathematics', 'English', 'Kiswahili', 'Biology', 'Chemistry', 'Physics', 'Civics', 'Geography', 'History'];
        foreach ($subjects as $sub) {
            Subject::updateOrCreate(['subject_name' => $sub]);
        }
    }
}
