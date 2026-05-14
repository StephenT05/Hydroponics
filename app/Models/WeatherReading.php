<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherReading extends Model
{
    use HasFactory;

    protected $table = 'weather_readings';

    protected $fillable = [
        'lat', 'lon', 'city', 'temperature', 'feels_like', 'humidity', 'pressure', 'description', 'main', 'icon', 'wind_speed', 'clouds', 'sunrise', 'sunset',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'temperature' => 'float',
        'feels_like' => 'float',
        'wind_speed' => 'float',
        'humidity' => 'integer',
        'pressure' => 'integer',
        'clouds' => 'integer',
        'sunrise' => 'integer',
        'sunset' => 'integer',
        'icon' => 'string',
    ];
}
