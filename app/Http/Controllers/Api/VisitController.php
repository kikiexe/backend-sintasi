<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medical_record_number' => ['required', 'string'],
        ], [
            'medical_record_number.required' => 'Nomor Rekam Medik wajib diisi.',
        ]);

        $patient = Patient::where(
            'medical_record_number',
            strtoupper(trim($validated['medical_record_number']))
        )->first();

        if (! $patient) {
            throw ValidationException::withMessages([
                'medical_record_number' => ['Nomor Rekam Medik tidak ditemukan dalam sistem.'],
            ]);
        }

        $visit = Visit::create([
            'patient_id' => $patient->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kunjungan pasien berhasil dibuat.',
            'data' => [
                'visit' => $visit->load('patient'),
                'total_patient_visits' => $patient->visits()->count(),
            ],
        ], 201);
    }
}
