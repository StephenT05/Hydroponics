<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_readings', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat', 8, 5)->nullable();
            $table->decimal('lon', 9, 5)->nullable();
            $table->string('city')->nullable();
            $table->double('temperature')->nullable();
            $table->double('feels_like')->nullable();
            $table->integer('humidity')->nullable();
            $table->integer('pressure')->nullable();
            $table->string('description')->nullable();
            $table->string('main')->nullable();
            $table->double('wind_speed')->nullable();
            $table->integer('clouds')->nullable();
            $table->bigInteger('sunrise')->nullable();
            $table->bigInteger('sunset')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};
