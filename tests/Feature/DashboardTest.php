<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the dashboard and latest telemetry', function (): void {
    DB::table('predictions')->insert([
        'image' => 'leaf_images/sample.jpg',
        'prediction' => 'Nitrogen deficiency',
        'confidence' => 0.93,
        'created_at' => now()->subMinutes(5),
        'updated_at' => now()->subMinutes(5),
    ]);

    DB::table('temperature_readings')->insert([
        'temperature' => 29.4,
        'created_at' => now()->subMinutes(3),
        'updated_at' => now()->subMinutes(3),
    ]);

    DB::table('tds_readings')->insert([
        'tds_value' => 640,
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Nitrogen deficiency')
        ->assertSee('93.0%')
        ->assertSee('29.4°C')
        ->assertSee('640 ppm')
        ->assertSee('High temperature warning')
        ->assertSee('Low nutrient warning');
});

it('redirects the home page to the dashboard', function (): void {
    $this->get('/')
        ->assertRedirect(route('dashboard'));
});

it('redirects the legacy history route to the dashboard history section', function (): void {
    $this->get('/history')
        ->assertRedirect('/dashboard#history');
});

it('returns live dashboard telemetry as json', function (): void {
    DB::table('temperature_readings')->insert([
        'temperature' => 30.2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tds_readings')->insert([
        'tds_value' => 610,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson(route('dashboard.live'))
        ->assertOk()
        ->assertJsonStructure([
            'latestPrediction',
            'latestTemperature',
            'latestTds',
            'warningStates',
            'overviewCards',
            'recentPredictions',
            'recentSensorLogs',
            'futureFeatures',
            'refreshInterval',
            'lastSyncedAt',
            'liveSyncedAt',
        ]);
});