<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email_hash' => hash('sha256', 'admin@sintasi.com')],
            [
                'name' => 'Admin Sintasi',
                'email' => 'admin@sintasi.com',
                'password' => Hash::make('password123'),
            ]
        );

        User::firstOrCreate(
            ['email_hash' => hash('sha256', 'petugas@sintasi.com')],
            [
                'name' => 'Petugas Medis',
                'email' => 'petugas@sintasi.com',
                'password' => Hash::make('password123'),
            ]
        );

        // Data pasien awal sesuai referensi Figma
        $patient1 = Patient::firstOrCreate(
            ['medical_record_number' => 'RM0001'],
            [
                'name' => 'NURFAZA M.',
                'nik' => '3214124123124121',
                'address' => 'Jl Wisata 2 No 18',
            ]
        );

        Patient::firstOrCreate(
            ['medical_record_number' => 'RM0002'],
            [
                'name' => 'NURFAZA D.',
                'nik' => '3214124123555532',
                'address' => 'Jl Wisata 2 No 18',
            ]
        );

        if ($patient1->visits()->count() === 0) {
            for ($i = 0; $i < 8; $i++) {
                Visit::create(['patient_id' => $patient1->id]);
            }
        }
    }
}
