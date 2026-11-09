<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginIdentifier = trim((string) ($validated['username'] ?? $validated['email'] ?? ''));

        if ($loginIdentifier === '') {
            throw ValidationException::withMessages([
                'username' => ['Username atau Email wajib diisi.'],
            ]);
        }

        $emailHash = hash('sha256', strtolower($loginIdentifier));

        // Pencarian memakai email_hash karena kolom email dienkripsi di database
        $user = User::query()
            ->where('email_hash', $emailHash)
            ->orWhere('name', $loginIdentifier)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.',
        ]);
    }
}
