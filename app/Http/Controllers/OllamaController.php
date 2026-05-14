<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class OllamaController extends Controller
{
    public function run(Request $request)
    {
        $data = $request->validate([
            'model' => ['sometimes', 'string'],
            'sensorData' => ['required', 'array'],
            'sensorData.waterTemperature' => ['nullable'],
            'sensorData.tds' => ['nullable'],
            'sensorData.waterLevel' => ['nullable'],
            'sensorData.weatherTemperature' => ['nullable'],
            'sensorData.weatherDescription' => ['nullable'],
        ]);

        $model = $data['model'] ?? 'gemma3:1b';
        $sensorData = $data['sensorData'];

        // Fetch latest image prediction (if any) so Ollama can consider it
        $latest = DB::table('predictions')->orderByDesc('id')->first();
        $latestPredictionText = $latest ? ($latest->prediction . ' (' . round($latest->confidence * 100, 1) . '%)') : 'none';

        $prompt = implode("\n", [
            'You are a hydroponics operations assistant.',
            'Analyze the sensor snapshot below and provide a professional status update for the grower.',
            'Focus on the current condition, likely risks, and one or two practical next actions.',
            'Do not ask questions. Do not mention that the data is incomplete. Do not invent readings.',
            'Use a calm, concise tone and return exactly three short bullet points with these labels:',
            '- Status:',
            '- Risk:',
            '- Action:',
            '',
            'Latest image AI deficiency prediction: '. $latestPredictionText,
            'Important: Treat the Latest image AI prediction above as authoritative about leaf condition; do not contradict it in your Status or Risk lines. If it indicates a deficiency, reflect that explicitly.',
            '',
            'Sensor snapshot:',
            'Water temperature: '.($sensorData['waterTemperature'] ?? 'unknown'),
            'TDS reading: '.($sensorData['tds'] ?? 'unknown'),
            'Water level: '.($sensorData['waterLevel'] ?? 'unknown'),
            'Weather temperature: '.($sensorData['weatherTemperature'] ?? 'unknown'),
            'Weather description: '.($sensorData['weatherDescription'] ?? 'unknown'),
        ]);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(60)
                ->connectTimeout(5)
                ->retry(2, 250)
                ->post('http://127.0.0.1:11434/api/generate', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                ])
                ->throw();

            $output = trim((string) $response->json('response', ''));

            if ($output === '') {
                Log::warning('Ollama returned an empty response.');

                return Response::json(['error' => 'Ollama returned an empty response.'], 500);
            }

            return Response::json(['output' => $output]);
        } catch (\Throwable $e) {
            Log::error('Ollama run exception: '.$e->getMessage());

            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
