<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('alamat');
            $table->enum('type', ['Karyawan', 'Peternak', 'Pedagang', 'Supplier', 'Truk']);
            $table->string('keterangan')->nullable();
            $table->decimal('bon', 15, 2)->nullable();
            $table->decimal('titipan', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
