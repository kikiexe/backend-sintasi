<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_number',
        'name',
        'nik',
        'nik_hash',
        'address',
    ];

    protected $hidden = [
        'nik_hash',
    ];

    protected $appends = [
        'visit_count',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
        ];
    }

    /**
     * Hitung otomatis SHA-256 dari NIK untuk cek duplikasi karena kolom nik terenkripsi
     */
    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            if ($patient->isDirty('nik') && ! empty($patient->nik)) {
                $patient->nik_hash = hash('sha256', (string) $patient->nik);
            }
        });
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Gunakan hasil eager-load visits_count jika tersedia untuk menghindari N+1 query
     */
    public function getVisitCountAttribute(): int
    {
        if (array_key_exists('visits_count', $this->attributes)) {
            return (int) $this->attributes['visits_count'];
        }

        return $this->visits()->count();
    }
}
