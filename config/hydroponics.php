<?php

return [
    // Tank dimensions
    'tank_height_cm' => env('TANK_HEIGHT_CM', 15.24), // 6 inches in cm, adjustable per tank

    // HC-SR04 ultrasonic sensor deadzone (minimum readable distance)
    'sensor_deadzone_cm' => env('SENSOR_DEADZONE_CM', 2.0),

    // Trimmed mean: percentage of data to remove from each end (0-50)
    'trimmed_mean_percent' => env('TRIMMED_MEAN_PERCENT', 10),
];
