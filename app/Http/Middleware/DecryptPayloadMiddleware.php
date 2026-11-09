<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptPayloadMiddleware
{
    /**
     * Dekripsi otomatis field kredensial terenkripsi AES-256-CBC pada payload request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = (string) config('app.payload_secret_key', env('PAYLOAD_SECRET_KEY', 'SINTASI_PAYLOAD_SECRET_KEY_32B!'));
        // Hash SHA-256 biner (32 byte) sebagai kunci AES-256
        $keyHash = hash('sha256', $secretKey, true);

        $fieldsToDecrypt = ['username', 'email', 'password'];
        $modifiedInput = [];

        foreach ($fieldsToDecrypt as $field) {
            $value = $request->input($field);
            if (! is_string($value) || empty($value)) {
                continue;
            }

            $decrypted = $this->decryptValue($value, $keyHash);
            if ($decrypted !== null) {
                $modifiedInput[$field] = $decrypted;
            }
        }

        if (! empty($modifiedInput)) {
            $request->merge($modifiedInput);
        }

        return $next($request);
    }

    /**
     * Dekripsi string berformat "iv:ciphertext" atau format bawaan CryptoJS "Salted__".
     * Mengembalikan null jika input berupa plaintext biasa
     */
    protected function decryptValue(string $encrypted, string $key): ?string
    {
        if (str_contains($encrypted, ':')) {
            [$ivRaw, $dataRaw] = explode(':', $encrypted, 2);
            $iv = base64_decode($ivRaw, true) ?: @hex2bin($ivRaw);
            $cipher = base64_decode($dataRaw, true) ?: @hex2bin($dataRaw);

            if ($iv && $cipher && strlen($iv) === 16) {
                $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }

        $rawBytes = base64_decode($encrypted, true);
        if ($rawBytes && str_starts_with($rawBytes, 'Salted__') && strlen($rawBytes) > 16) {
            $salt = substr($rawBytes, 8, 8);
            $ciphertext = substr($rawBytes, 16);

            $derived = $this->evpBytesToKey($key, $salt, 32, 16);
            $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $derived['key'], OPENSSL_RAW_DATA, $derived['iv']);
            if ($decrypted !== false) {
                return $decrypted;
            }
        }

        return null;
    }

    /**
     * Turunkan key dan IV menggunakan EVP_BytesToKey (MD5) agar kompatibel dengan CryptoJS default
     *
     * @return array{key: string, iv: string}
     */
    protected function evpBytesToKey(string $password, string $salt, int $keyLen = 32, int $ivLen = 16): array
    {
        $dtot = '';
        $d = '';
        while (strlen($dtot) < ($keyLen + $ivLen)) {
            $d = md5($d . $password . $salt, true);
            $dtot .= $d;
        }

        return [
            'key' => substr($dtot, 0, $keyLen),
            'iv' => substr($dtot, $keyLen, $ivLen),
        ];
    }
}
