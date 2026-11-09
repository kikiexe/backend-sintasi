<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_number')->unique(); // Format urut: RM0001, RM0002, dst
            $table->string('name');
            $table->text('nik'); // Disimpan terenkripsi di database
            $table->string('nik_hash', 64)->unique(); // Hash SHA-256 deterministik untuk validasi duplikasi
            $table->text('address');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
