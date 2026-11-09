<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query()->withCount('visits');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('medical_record_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('id', 'asc')->paginate($request->integer('per_page', 15));

        $lastPatient = Patient::orderByDesc('id')->first();
        $nextNumber = $lastPatient ? ((int) preg_replace('/\D/', '', $lastPatient->medical_record_number) + 1) : 1;
        $nextMrn = sprintf('RM%04d', $nextNumber);

        return response()->json([
            'status' => 'success',
            'next_medical_record_number' => $nextMrn,
            'data' => $patients,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'digits:16'],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit angka.',
            'name.required' => 'Nama pasien wajib diisi.',
            'name.min' => 'Nama pasien minimal 3 karakter.',
            'address.required' => 'Alamat wajib diisi.',
            'address.min' => 'Alamat minimal 5 karakter.',
        ]);

        // Cek duplikasi memakai SHA-256 hash karena kolom nik dienkripsi
        $nikHash = hash('sha256', $validated['nik']);
        if (Patient::where('nik_hash', $nikHash)->exists()) {
            throw ValidationException::withMessages([
                'nik' => ['NIK ini sudah terdaftar dalam sistem.'],
            ]);
        }

        // Gunakan row lock dalam transaksi agar nomor RM berurutan (RM0001) bebas race condition
        $patient = DB::transaction(function () use ($validated) {
            $lastPatient = Patient::orderByDesc('id')->lockForUpdate()->first();
            $nextNumber = $lastPatient ? ((int) preg_replace('/\D/', '', $lastPatient->medical_record_number) + 1) : 1;
            $newMrn = sprintf('RM%04d', $nextNumber);

            return Patient::create([
                'medical_record_number' => $newMrn,
                'name' => $validated['name'],
                'nik' => $validated['nik'],
                'address' => $validated['address'],
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran pasien berhasil.',
            'data' => $patient,
        ], 201);
    }

    public function show(string $idOrMrn): JsonResponse
    {
        $patient = Patient::query()
            ->withCount('visits')
            ->where('id', $idOrMrn)
            ->orWhere('medical_record_number', strtoupper(trim($idOrMrn)))
            ->first();

        if (! $patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pasien tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $patient,
        ]);
    }
}
