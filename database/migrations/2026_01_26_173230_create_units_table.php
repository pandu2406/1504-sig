<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('idsbr')->unique()->nullable(); // Unique identifier from Excel
            $table->string('kdkec');
            $table->string('kddesa');
            $table->string('nama_usaha');
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status_awal', ['HAS_COORD', 'NO_COORD'])->default('NO_COORD');
            $table->enum('current_status', ['PENDING', 'VERIFIED'])->default('PENDING');
            $table->longText('raw_data')->nullable(); // Store full Excel row
            $table->string('last_updated_by')->nullable(); // Changed to string for Manual Name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
