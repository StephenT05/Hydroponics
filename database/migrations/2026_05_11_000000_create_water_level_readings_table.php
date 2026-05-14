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
        Schema::create('water_level_readings', function (Blueprint $table) {
            $table->id();

            $table->float('distance_cm'); // Raw HC-SR04 distance reading
            $table->float('percentage'); // Calculated percentage full (0-100)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_level_readings');
    }
};
