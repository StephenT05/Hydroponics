# Hydrolink AI — Hydroponics Monitoring & Control Dashboard

A real-time hydroponic crop monitoring system combining IoT sensor integration, plant health AI/ML classification, and local LLM-powered operational analysis.

## System Summary

**Hydrolink AI** orchestrates data from ESP8266/ESP32 IoT devices (temperature, TDS/EC, water level, plant images) and integrates external weather APIs. Machine learning models classify plant deficiency types from leaf images. A local Ollama instance provides AI-driven operational insights based on sensor snapshots. The dashboard displays live metrics, prediction history, and actionable recommendations for growers.

### Key Features
- **Live sensor telemetry** polling (45s refresh) from temperature, conductivity, and water level sensors
- **Plant health classification** via TensorFlow/Keras (EfficientNetB0) → detects Healthy, N/P/K/Zn deficiency
- **Weather integration** (OpenWeather API) with location override and caching
- **Local LLM analysis** (Ollama + Gemma 3.1B) → sensor snapshot → 3-point operational summary (Status/Risk/Action)
- **Persistent storage** (SQLite) of all readings, predictions, and weather data
- **Dark-mode UI** (Tailwind CSS v4) optimized for low-light crop environments

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend** | Laravel Framework | v13 |
| **PHP Runtime** | PHP | 8.4 |
| **Frontend** | Blade templates + Vite | 5.x |
| **CSS Framework** | Tailwind CSS | v4 |
| **Database** | SQLite | — |
| **Testing** | Pest | v4 |
| **Code Formatting** | Laravel Pint | v1 |

---

## AI / ML Components

### Plant Health Classification
- **Model**: `ai/plant_model.keras` (TensorFlow/Keras)
- **Architecture**: EfficientNetB0 (pretrained ImageNet) + 2-phase fine-tuning
  - Phase 1: Frozen backbone, train classification head (15 epochs)
  - Phase 2: Fine-tune top 40 backbone layers (50 epochs max, early stop)
- **Classes**: Healthy, Deficient Nitrogen, Deficient Phosphorus, Deficient Potassium, Deficient Zinc
- **Input**: 224×224 RGB JPEGs from ESP32-CAM
- **Output**: Prediction class + confidence (0–1)
- **Training script**: `ai/train.py` (uses augmented dataset in `ai/dataset/` subdirs)

### Local LLM Integration
- **Service**: Ollama (self-hosted, separate process)
- **Model**: Gemma 3.1B (default, configurable)
- **API**: HTTP POST to `http://127.0.0.1:11434/api/generate`
- **Prompt**: Structured sensor snapshot + latest plant prediction → 3 bullet-point analysis
- **Timeout**: 60s, with 2 retries

---

## Directory Structure & File Routes

```
Hydroponics/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php        # Main dashboard view + live JSON polling
│   │   ├── SensorController.php           # IoT sensor endpoints (temperature, tds, water-level, upload-leaf)
│   │   └── OllamaController.php           # Local LLM orchestration (/ollama/run)
│   ├── Models/
│   │   └── WeatherReading.php             # Weather data persistence model
│   ├── Services/
│   │   └── WeatherService.php             # OpenWeather API client + caching
│   └── Providers/
│       └── AppServiceProvider.php
├── config/
│   ├── hydroponics.php                    # Tank height, sensor deadzone, trimmed mean settings
│   └── openweather.php                    # API key, lat/lon, cache duration, SSL verify
├── database/
│   ├── migrations/
│   │   ├── 2026_05_11_000000_create_water_level_readings_table.php
│   │   ├── 2026_05_11_120000_create_weather_readings_table.php
│   │   └── 2026_05_11_130000_add_icon_to_weather_readings.php
│   └── factories/
│       └── UserFactory.php
├── resources/
│   ├── views/
│   │   └── dashboard.blade.php            # Main UI (hero, sensor cards, history, AI monitor)
│   ├── js/
│   │   └── app.js                         # Frontend polling, Ollama button handler
│   └── css/
│       └── app.css
├── routes/
│   └── web.php                            # API routes: /dashboard, /temperature, /tds, /water-level, /upload-leaf, /ollama/run
├── ai/
│   ├── predict.py                         # TensorFlow inference script (called by SensorController on image upload)
│   ├── train.py                           # Model training pipeline (EfficientNetB0 2-phase)
│   ├── plant_model.keras                  # Trained model file
│   ├── venv/                              # Python virtual environment (local)
│   └── dataset/
│       ├── Healthy/
│       ├── Nitrogen/
│       ├── Phosphorus/
│       ├── Potassium/
│       └── Zinc/
├── storage/
│   ├── app/
│   │   └── public/
│   │       ├── leaf_images/               # ESP32-CAM uploads stored here
│   │       └── private/
│   └── logs/                              # Laravel & application logs
├── bootstrap/
│   ├── app.php                            # CSRF exemptions for sensor endpoints
│   └── providers.php
├── public/
│   ├── index.php                          # Entry point
│   └── build/
│       └── manifest.json                  # Vite asset manifest
├── .env                                   # Environment (API keys, APP_URL, DB credentials)
├── composer.json                          # PHP dependencies
├── package.json                           # Frontend dependencies (Tailwind, Vite)
├── phpunit.xml                            # Pest test configuration
├── vite.config.js                         # Frontend build/dev config
├── artisan                                # Laravel CLI
└── README.md                              # This file
```

### Key API Routes

| Method | Endpoint | Controller | Purpose |
|--------|----------|-----------|---------|
| GET | `/` | — | Redirects to `/dashboard` |
| GET | `/dashboard` | `DashboardController@index` | Render UI (initial page load) |
| GET | `/dashboard/live` | `DashboardController@live` | JSON response (polling endpoint) |
| POST | `/temperature` | `SensorController@temperature` | Store water temperature reading |
| POST | `/tds` | `SensorController@tds` | Store TDS/conductivity reading |
| POST | `/water-level` | `SensorController@waterLevel` | Store water level (distance + %) |
| POST | `/upload-leaf` | `SensorController@uploadLeaf` | Receive ESP32-CAM image + run AI |
| POST | `/ollama/run` | `OllamaController@run` | POST sensor snapshot to local LLM |
| POST | `/settings/weather-location` | `DashboardController@updateWeatherLocation` | Override weather lat/lon |

---

## Dependencies

### PHP (Backend)
See `composer.json` for full list. Key packages:
- `laravel/framework` v13 — core
- `laravel/prompts` v0 — CLI prompts
- `laravel/pint` v1 — code formatter
- `laravel/boost` v2 — MCP tools for agent development
- `pestphp/pest` v4 — testing framework
- `phpunit/phpunit` v12 — unit test runner

### JavaScript/Frontend
See `package.json`:
- `tailwindcss` v4 — styling
- `vite` — bundler
- (others: postcss, autoprefixer)

### Python (ML)
In `ai/venv/`:
- `tensorflow` — deep learning framework
- `numpy` — numerical computing
- `scikit-learn` — preprocessing/metrics
- Installed via `pip` (see `ai/venv/Scripts/`)

### External Services (Run Separately)
  - Download: https://ollama.ai
  - Run: `ollama serve` (listens on `127.0.0.1:11434`)
  - Model: `gemma3:1b` (or specify in dashboard POST)
  - Not installed as Laravel dependency; runs as background process

  - Endpoint: `https://api.openweathermap.org/data/2.5/weather`
  - Requires API key in `.env` (`OPENWEATHER_API_KEY`)
  - Cached 10 minutes (configurable)


## Hardware

Recommended hardware used and tested with this project:
- **ESP32-CAM** — image capture for plant leaf photos (uploads to `/upload-leaf`).
- **ESP8266 / ESP32 (generic)** — microcontroller for sensor readings and HTTP POSTs.
- **TDS / EC probe** (analog conductivity sensor) — measures nutrient solution conductivity.
- **Waterproof temperature sensor (e.g., DS18B20)** or compatible thermistor — water temperature.
- **HC-SR04 ultrasonic sensor** — measures water tank distance (used to compute percentage fill).
- **Power supply, relays/MOSFETs, wiring** — 5V/3.3V power and switching for pumps/actuators.
- **Optional**: pH probe, peristaltic pump, float switches for redundancy.

## Libraries Required (installation)

These are the primary libraries and tools you should have installed before running the project.

- PHP / Composer (backend): install Composer, then run `composer install` to fetch packages listed in `composer.json` (key packages: `laravel/framework`, `laravel/boost`, `laravel/pint`, `pestphp/pest`).
- Node.js / npm (frontend): install Node.js and npm, then run `npm install` (key packages: `tailwindcss`, `vite`, `laravel-vite-plugin`).
- Python (ML inference/training): create a virtualenv and install packages:

```bash
python -m venv ai/venv
ai\venv\Scripts\activate     # Windows
pip install --upgrade pip
pip install tensorflow numpy scikit-learn pillow
```

- Ollama (local LLM): install and run the Ollama binary (https://ollama.ai). This is a separate service — not a composer/npm dependency.

- Microcontroller firmware libraries (install via Arduino Library Manager or PlatformIO):
  - `ArduinoJson` — JSON serialization for HTTP payloads
  - `OneWire` + `DallasTemperature` — if using DS18B20
  - `TdsSensor` or equivalent sketch code to read analog TDS probes (many projects use simple ADC scaling)
  - (ESP boards include `WiFi.h` / `ESP8266WiFi.h` by default)

## Quick How to Run (short)

1. Ensure system requirements installed: PHP 8.4 + Composer, Node + npm, Python 3.9+, Ollama running locally.
2. Clone repo and install dependencies:

```bash
git clone https://github.com/StephenT05/Hydroponics.git
cd Hydroponics
composer install
npm install
```

3. Configure environment and database:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

4. Prepare Python environment for inference (optional, required for `/upload-leaf` AI):

```bash
python -m venv ai/venv
ai\venv\Scripts\activate
pip install tensorflow numpy pillow scikit-learn
```

5. Start services:

```bash
# Start Laravel (bind to LAN for devices)
php artisan serve --host=0.0.0.0 --port=8000

# In a separate shell: start Ollama (must be installed separately)
ollama serve

# Optional: start Vite dev server
npm run dev
```

IoT devices should POST sensor payloads to `http://<PC-LAN-IP>:8000/*` (see API routes above).

## Getting Started

### Prerequisites
- PHP 8.4 + Composer
- Node.js + npm
- Python 3.9+ (with venv)
- Ollama (download & start separately)

### Installation
```bash
# Clone repo
git clone https://github.com/StephenT05/Hydroponics.git
cd Hydroponics

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Configure settings (in .env)
OPENWEATHER_API_KEY=your_api_key
OPENWEATHER_LATITUDE=18.03
OPENWEATHER_LONGITUDE=120.53
TANK_HEIGHT_CM=15.24
SENSOR_DEADZONE_CM=2.0

# Migrate database
php artisan migrate

# Build frontend (or use Vite dev server)
npm run build
```

### Running the System
```bash
# Terminal 1: Start Laravel (binds to 0.0.0.0 for IoT device access)
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Start Ollama (separate service)
ollama serve

# Terminal 3 (optional, for dev): Watch frontend
npm run dev

# Open browser
http://localhost:8000/dashboard
```

### Accessing from IoT Devices
Devices on your local network can POST to:
- `http://<PC-LAN-IP>:8000/temperature` → `{"temperature": 24.5}`
- `http://<PC-LAN-IP>:8000/tds` → `{"tds": 450}`
- `http://<PC-LAN-IP>:8000/water-level` → `{"distance_cm": 6.9}`
- `http://<PC-LAN-IP>:8000/upload-leaf` → binary image (raw body)

Replace `<PC-LAN-IP>` with your Windows PC's IP (e.g., `192.168.0.105`).

---

## Testing
```bash
php artisan test --compact
```

---

## Notes
- CSRF protection is **disabled** on sensor endpoints (`bootstrap/app.php`) so IoT devices can POST without tokens.
- Ollama must be running before clicking "Send to Ollama" on the dashboard; the app makes synchronous HTTP calls (60s timeout).
- Plant ML model is frozen in git; retrain with `python ai/train.py` if you add new dataset images.
- Water level reads HC-SR04 ultrasonic distance and converts to percentage using tank height config.

---

## License
This project is open-source under the MIT license.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
