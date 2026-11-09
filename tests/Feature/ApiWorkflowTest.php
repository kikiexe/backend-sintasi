<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_with_plain_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin@sintasi.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_with_encrypted_payload(): void
    {
        $secretKey = env('PAYLOAD_SECRET_KEY', 'SINTASI_PAYLOAD_SECRET_KEY_32B!');
        $keyHash = hash('sha256', $secretKey, true);

        $ivUsername = random_bytes(16);
        $cipherUsername = openssl_encrypt('admin@sintasi.com', 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $ivUsername);
        $encUsername = base64_encode($ivUsername) . ':' . base64_encode($cipherUsername);

        $ivPassword = random_bytes(16);
        $cipherPassword = openssl_encrypt('password123', 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $ivPassword);
        $encPassword = base64_encode($ivPassword) . ':' . base64_encode($cipherPassword);

        $response = $this->postJson('/api/login', [
            'username' => $encUsername,
            'password' => $encPassword,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_database_columns_email_and_nik_are_encrypted_at_rest(): void
    {
        $rawUserEmail = DB::table('users')->where('name', 'Admin Sintasi')->value('email');
        $this->assertNotEquals('admin@sintasi.com', $rawUserEmail);

        $rawPatientNik = DB::table('patients')->where('medical_record_number', 'RM0001')->value('nik');
        $this->assertNotEquals('3214124123124121', $rawPatientNik);
    }

    public function test_patient_registration_and_automatic_rm_generation(): void
    {
        $user = User::first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/patients', [
                'nik' => '3201011112990003',
                'name' => 'Ahmad Dahlan',
                'address' => 'Jl. Pahlawan No. 99, Yogyakarta',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.medical_record_number', 'RM0003');

        $detail = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/patients/RM0003');

        $detail->assertStatus(200)
            ->assertJsonPath('data.name', 'Ahmad Dahlan')
            ->assertJsonPath('data.nik', '3201011112990003');
    }

    public function test_patient_registration_rejects_invalid_or_duplicate_nik(): void
    {
        $user = User::first();
        $token = $user->createToken('test')->plainTextToken;

        // NIK tidak valid (< 16 digit).
        $invalidRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/patients', [
                'nik' => '12345',
                'name' => 'Test Invalid',
                'address' => 'Jl. Test No. 1',
            ]);
        $invalidRes->assertStatus(422)->assertJsonValidationErrors(['nik']);

        // NIK duplikat dari data seeder.
        $dupRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/patients', [
                'nik' => '3214124123124121',
                'name' => 'Duplicate Patient',
                'address' => 'Jl. Test No. 2',
            ]);
        $dupRes->assertStatus(422)->assertJsonValidationErrors(['nik']);
    }

    public function test_visit_creation_and_visit_count_increment(): void
    {
        $user = User::first();
        $token = $user->createToken('test')->plainTextToken;

        $patient = Patient::where('medical_record_number', 'RM0001')->first();
        $initialVisits = $patient->visits()->count();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/visits', [
                'medical_record_number' => 'RM0001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_patient_visits', $initialVisits + 1);
    }
}
